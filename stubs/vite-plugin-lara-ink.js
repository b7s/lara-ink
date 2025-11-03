/**
 * Vite Plugin for LaraInk Hot Reload
 *
 * This plugin watches LaraInk resources and triggers rebuild + browser reload
 *
 * Usage in vite.config.js:
 *
 * import laraInkPlugin from './vite-plugin-lara-ink.js';
 *
 * export default {
 *   plugins: [
 *     laraInkPlugin() // No configuration needed!
 *   ]
 * }
 *
 * Or with custom options:
 *
 * export default {
 *   plugins: [
 *     laraInkPlugin({
 *       watchPaths: ['resources/lara-ink/**'],
 *       buildCommand: 'php artisan lara-ink:build',
 *       debounce: 1000
 *     })
 *   ]
 * }
 */

import { exec } from "child_process";
import fs from "fs";
import path from "path";
import { promisify } from "util";

const execAsync = promisify(exec);

const ansiColors = {
  blue: "\x1b[34m",
  green: "\x1b[32m",
  red: "\x1b[31m",
  bold: "\x1b[1m",
  reset: "\x1b[0m",
};

const formatTag = (color) =>
  `${ansiColors.bold}${ansiColors[color]}[LaraInk]${ansiColors.reset}`;

const hotFilePath = path.resolve(process.cwd(), "public/hot");

const cleanupHandlers = new Set();

const resolveHotUrl = (server) => {
  const config = server.config.server ?? {};
  const protocol = config.https ? "https" : "http";

  let host =
    typeof config.host === "string"
      ? config.host
      : config.host && typeof config.host === "boolean"
      ? "localhost"
      : "localhost";

  if (host === "0.0.0.0" || host === "::") {
    host = "localhost";
  }

  let port = config.port ?? 5173;

  const address = server?.httpServer?.address?.();
  if (address && typeof address === "object" && address.port) {
    port = address.port;
    if (address.address && address.address !== "::" && address.address !== "0.0.0.0") {
      host = address.address;
    }
  }

  return `${protocol}://${host}:${port}`;
};

const ensureHotFile = (url) => {
  try {
    fs.mkdirSync(path.dirname(hotFilePath), { recursive: true });
    fs.writeFileSync(hotFilePath, url);
  } catch (error) {
    console.warn(`${formatTag("yellow")} Failed to write Vite hot file:`, error.message);
  }
};

const removeHotFile = () => {
  if (fs.existsSync(hotFilePath)) {
    try {
      fs.unlinkSync(hotFilePath);
    } catch (error) {
      console.warn(`${formatTag("yellow")} Failed to remove Vite hot file:`, error.message);
    }
  }
};

const registerCleanup = (fn) => {
  cleanupHandlers.add(fn);
};

const setupCleanupHooks = () => {
  const runCleanup = () => {
    cleanupHandlers.forEach((handler) => {
      try {
        handler();
      } catch (error) {
        console.warn(`${formatTag("yellow")} Cleanup handler failed:`, error.message);
      }
    });
    cleanupHandlers.clear();
  };

  process.once("exit", runCleanup);
  process.once("SIGINT", runCleanup);
  process.once("SIGTERM", runCleanup);
};

setupCleanupHooks();

export default function laraInkPlugin(options) {
  // Default configuration - no parameters needed
  const config = {
    watchPaths: ["resources/lara-ink/**"],
    buildCommand: "php artisan lara-ink:build-selective",
    debounce: 1000,
    selective: true, // Use selective build by default
    ...options, // Override with user options if provided
  };

  const { watchPaths, buildCommand, debounce, selective } = config;

  const buildCommandForFile = (absolutePath) => {
    // Allow placeholder replacement
    if (buildCommand.includes('{file}')) {
      return buildCommand.replace('{file}', `"${absolutePath}"`);
    }

    // Auto-detect selective commands (default behaviour)
    const isSelectiveCommand =
      selective || buildCommand.includes('build-selective') || buildCommand.includes('--file');

    if (isSelectiveCommand) {
      return `${buildCommand} "${absolutePath}"`;
    }

    return buildCommand;
  };

  let building = false;
  let buildTimeout = null;

  return {
    name: "vite-plugin-lara-ink",

    async configureServer(server) {
      const updateHotFile = () => {
        const hotUrl = resolveHotUrl(server);
        ensureHotFile(hotUrl);
      };

      const cleanup = () => {
        removeHotFile();
      };

      registerCleanup(cleanup);

      // Create hot file immediately so PHP build includes HMR client
      updateHotFile();

      if (server.httpServer) {
        if (server.httpServer.listening) {
          updateHotFile();
        } else {
          server.httpServer.once("listening", updateHotFile);
        }

        server.httpServer.once("close", cleanup);
      } else {
        updateHotFile();
      }

      // Initial build on server start
      console.log(`${formatTag("blue")} Checking for initial build...`);
      try {
        const { stdout } = await execAsync("php artisan lara-ink:build");
        console.log(`${formatTag("green")} ✓ Initial build completed\n`);
      } catch (error) {
        console.error(`${formatTag("red")} Initial build failed:`, error.message);
      }

      const rebuild = async (changedPath) => {
        if (building) return;

        building = true;
        const relativePath = changedPath.replace(process.cwd() + "/", "");
        console.log(`\n${formatTag("blue")} Changes detected in: ${ansiColors.bold}${relativePath}${ansiColors.reset}`);

        try {
          // Use selective build with absolute file path
          const absolutePath = changedPath.startsWith("/")
            ? changedPath
            : `${process.cwd()}/${changedPath}`;

          const command = buildCommandForFile(absolutePath);

          if (!command.includes('build-selective') && selective) {
            console.warn(
              `${formatTag("yellow")} Selective rebuild enabled but command does not look selective. ` +
              `Update buildCommand option or add '{file}' placeholder.`
            );
          }

          const { stdout, stderr } = await execAsync(command);

          if (stderr && !stderr.includes('INFO') && !stderr.includes('✓')) {
            console.error(`${formatTag("red")} Build error:`, stderr);
          } else {
            // Parse output to show what was rebuilt
            if (stdout) {
              const lines = stdout.split('\n').filter(line => line.trim());
              const successLine = lines.find(line => line.includes('page(s) compiled'));
              if (successLine) {
                console.log(`${formatTag("green")} ${successLine.trim()}`);
              } else {
                console.log(`${formatTag("green")} ✓ Build completed`);
              }
            } else {
              console.log(`${formatTag("green")} ✓ Build completed`);
            }
            
            // Force full page reload
            server.ws.send({
              type: "full-reload",
              path: "*",
            });
          }
        } catch (error) {
          console.error(`${formatTag("red")} Build failed:`, error.message);
        } finally {
          building = false;
        }
      };

      const debouncedRebuild = (path) => {
        clearTimeout(buildTimeout);
        buildTimeout = setTimeout(() => rebuild(path), debounce);
      };

      // Watch LaraInk resources
      server.watcher.add(watchPaths);

      server.watcher.on("change", (path) => {
        if (
          watchPaths.some((pattern) => {
            const regex = new RegExp(
              pattern.replace("**", ".*").replace("*", "[^/]*")
            );
            return regex.test(path);
          })
        ) {
          debouncedRebuild(path);
        }
      });

      console.log(
        `${formatTag("blue")} Hot reload ${ansiColors.green}enabled${
          ansiColors.reset
        } for: ${watchPaths.join(", ")}`
      );
    },
  };
}
