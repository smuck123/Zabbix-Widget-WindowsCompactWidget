# WindowsCompactWidget

A compact Zabbix dashboard widget module for Windows hosts.

## Features

- Compact table for selected hosts.
- Columns for CPU, memory, process count, cores, total memory, disks, traffic, uptime.
- Optional macro columns with custom labels.
- Disk detail cards with optional performance rows.
- Clickable metric values (CPU, memory, sessions, and disk performance rows) that open a small history popup graph.
- Configurable column order.

## Requirements

- Zabbix with dashboard widget module support.
- Windows hosts with relevant monitored items (CPU, memory, disk, etc.).

## Installation

1. Copy this module directory to your Zabbix modules path.
2. Ensure folder name is `WindowsCompactWidget` (or update module path accordingly).
3. Reload Zabbix frontend and enable the module.
4. Add **WindowsCompactWidget** to a dashboard.

## Widget configuration

In widget settings you can:

- Select hosts.
- Enable/disable built-in columns.
- Enable compact mode.
- Enable disk performance section.
- Define custom column order (one key per line).
- Add macro names and optional labels.

### Common column keys

- `host`
- `cpu`
- `memory`
- `sessions`
- `cores`
- `total_memory`
- `disks`
- `traffic`
- `uptime`
- `macro:{$YOUR_MACRO}`

## Notes

- History popups require available history records in Zabbix for the item.
- Disk performance rows are shown only when **Show disk performance** is enabled.
