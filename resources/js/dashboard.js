import { ref, onValue } from "firebase/database";
import { database } from "./firebase";

const pathname = window.location.pathname;
const watchedPaths = pathname.startsWith("/features/inventory")
    ? ["inventory", "relief_packs"]
    : pathname === "/features/dashboard"
      ? [
            "inventory",
            "relief_packs",
            "occupiedTents",
            "scanEvents",
            "reliefPackScans",
            "auditLogs",
        ]
      : pathname === "/features/evacuation"
        ? ["occupiedTents", "scanEvents"]
        : pathname === "/features/qr"
          ? ["inventory", "relief_packs", "reliefPackScans", "auditLogs"]
          : [];

const latestValues = new Map();
let refreshTimer;

watchedPaths.forEach((path) => {
    onValue(
        ref(database, path),
        (snapshot) => {
            const value = JSON.stringify(snapshot.val());
            const previousValue = latestValues.get(path);
            latestValues.set(path, value);

            if (previousValue !== undefined && previousValue !== value) {
                window.clearTimeout(refreshTimer);
                refreshTimer = window.setTimeout(() => {
                    window.location.reload();
                }, 250);
            }
        },
        (error) => {
            console.error(`Unable to watch Firebase updates for "${path}".`, error);
        },
    );
});
