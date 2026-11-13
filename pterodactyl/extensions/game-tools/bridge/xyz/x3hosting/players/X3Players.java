package xyz.x3hosting.players;

import org.bukkit.OfflinePlayer;
import org.bukkit.command.Command;
import org.bukkit.command.CommandSender;
import org.bukkit.plugin.java.JavaPlugin;

import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.StandardCopyOption;
import java.time.Instant;
import java.util.LinkedHashMap;
import java.util.HashSet;
import java.util.Map;
import java.util.Set;
import java.util.TreeSet;
import java.util.regex.Pattern;
import java.util.stream.Collectors;

public final class X3Players extends JavaPlugin {
    private static final Pattern ID = Pattern.compile("[a-f0-9]{32}");
    private static final Pattern PLAYER = Pattern.compile("[A-Za-z0-9_]{1,16}");
    private static final Map<String, String> COMMANDS = Map.of(
        "allow", "minecraft:whitelist add ",
        "unallow", "minecraft:whitelist remove ",
        "ban", "minecraft:ban ",
        "pardon", "minecraft:pardon ",
        "kick", "minecraft:kick ",
        "whitelist-on", "minecraft:whitelist on",
        "whitelist-off", "minecraft:whitelist off"
    );

    private final Map<String, Boolean> receipts = new LinkedHashMap<>();
    private final Set<String> pending = new HashSet<>();
    private boolean reportedIoError;

    @Override
    public void onEnable() {
        writeState(true);
        getServer().getScheduler().runTaskTimer(this, () -> writeState(true), 100L, 100L);
    }

    @Override
    public void onDisable() {
        writeState(false);
    }

    @Override
    public boolean onCommand(CommandSender sender, Command command, String label, String[] args) {
        if (sender != getServer().getConsoleSender() || args.length < 2 || args.length > 3 || !ID.matcher(args[0]).matches()) {
            return false;
        }
        String id = args[0];
        String action = args[1];
        String vanilla = COMMANDS.get(action);
        if (vanilla == null || receipts.containsKey(id) || pending.contains(id) || pending.size() >= 64) {
            return true;
        }
        boolean toggle = action.startsWith("whitelist-");
        if ((toggle && args.length != 2) || (!toggle && (args.length != 3 || !PLAYER.matcher(args[2]).matches()))) {
            return false;
        }
        String target = toggle ? "" : args[2];
        pending.add(id);
        try {
            boolean wasOnline = action.equals("kick") && hasName(getServer().getOnlinePlayers(), target);
            if (action.equals("kick") && !wasOnline) {
                finish(id, false);
                return true;
            }
            getServer().dispatchCommand(getServer().getConsoleSender(), vanilla + target);
            getServer().getScheduler().runTaskLater(this, () -> verify(id, action, target, 12), 5L);
        } catch (RuntimeException exception) {
            getLogger().warning("Could not run a player action: " + exception.getClass().getSimpleName());
            finish(id, false);
        }

        return true;
    }

    private void verify(String id, String action, String target, int attempts) {
        boolean ok;
        try {
            ok = switch (action) {
                case "allow" -> hasName(getServer().getWhitelistedPlayers(), target);
                case "unallow" -> !hasName(getServer().getWhitelistedPlayers(), target);
                case "ban" -> hasName(getServer().getBannedPlayers(), target);
                case "pardon" -> !hasName(getServer().getBannedPlayers(), target);
                case "kick" -> !hasName(getServer().getOnlinePlayers(), target);
                case "whitelist-on" -> getServer().hasWhitelist();
                case "whitelist-off" -> !getServer().hasWhitelist();
                default -> false;
            };
        } catch (RuntimeException exception) {
            getLogger().warning("Could not verify a player action: " + exception.getClass().getSimpleName());
            finish(id, false);
            return;
        }
        if (ok || attempts == 0) {
            finish(id, ok);
        } else {
            getServer().getScheduler().runTaskLater(this, () -> verify(id, action, target, attempts - 1), 10L);
        }
    }

    private void finish(String id, boolean ok) {
        pending.remove(id);
        if (receipts.size() >= 64) {
            receipts.remove(receipts.keySet().iterator().next());
        }
        receipts.put(id, ok);
        writeState(true);

    }

    private static boolean hasName(Iterable<? extends OfflinePlayer> players, String name) {
        for (OfflinePlayer player : players) {
            if (name.equalsIgnoreCase(player.getName())) {
                return true;
            }
        }
        return false;
    }

    private static String list(Iterable<? extends OfflinePlayer> players) {
        TreeSet<String> names = new TreeSet<>(String.CASE_INSENSITIVE_ORDER);
        for (OfflinePlayer player : players) {
            String name = player.getName();
            if (name != null && PLAYER.matcher(name).matches()) {
                names.add(name);
            }
        }
        return names.stream().limit(2500).map(name -> "\"" + name + "\"").collect(Collectors.joining(",", "[", "]"));
    }

    private void writeState(boolean ready) {
        Path directory = getDataFolder().toPath();
        Path temporary = null;
        try {
            Files.createDirectories(directory);
            String results = receipts.entrySet().stream().map(entry -> "\"" + entry.getKey() + "\":" + entry.getValue())
                .collect(Collectors.joining(",", "{", "}"));
            String json = "{\"v\":1,\"ready\":" + ready + ",\"time\":" + Instant.now().getEpochSecond()
                + ",\"enabled\":" + (ready && getServer().hasWhitelist())
                + ",\"online\":" + (ready ? list(getServer().getOnlinePlayers()) : "[]")
                + ",\"allowlist\":" + (ready ? list(getServer().getWhitelistedPlayers()) : "[]")
                + ",\"banned\":" + (ready ? list(getServer().getBannedPlayers()) : "[]")
                + ",\"receipts\":" + results + "}";
            temporary = Files.createTempFile(directory, "panel-state-", ".tmp");
            Files.writeString(temporary, json);
            Files.move(temporary, directory.resolve("panel-state.json"), StandardCopyOption.ATOMIC_MOVE, StandardCopyOption.REPLACE_EXISTING);
            reportedIoError = false;
        } catch (IOException | RuntimeException exception) {
            if (!reportedIoError) {
                getLogger().warning("Could not update panel state: " + exception.getClass().getSimpleName());
                reportedIoError = true;
            }
        } finally {
            if (temporary != null) {
                try {
                    Files.deleteIfExists(temporary);
                } catch (IOException ignored) {
                    // The next state update uses a fresh temporary name.
                }
            }
        }
    }
}
