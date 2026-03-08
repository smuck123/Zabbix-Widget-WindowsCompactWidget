<?php

namespace Modules\WindowsCompactWidget\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;

class WidgetView extends CControllerDashboardWidgetView {

    protected function doAction(): void {
        $hostids = $this->fields_values['hostids'] ?? [];
        $show_cpu = (int) ($this->fields_values['show_cpu'] ?? 1);
        $show_memory = (int) ($this->fields_values['show_memory'] ?? 1);
        $show_sessions = (int) ($this->fields_values['show_sessions'] ?? 1);
        $show_cores = (int) ($this->fields_values['show_cores'] ?? 1);
        $show_total_memory = (int) ($this->fields_values['show_total_memory'] ?? 1);
        $show_disks = (int) ($this->fields_values['show_disks'] ?? 1);
        $show_traffic = (int) ($this->fields_values['show_traffic'] ?? 1);
        $show_uptime = (int) ($this->fields_values['show_uptime'] ?? 1);
        $compact_mode = (int) ($this->fields_values['compact_mode'] ?? 1);
        $show_disk_perf = (int) ($this->fields_values['show_disk_perf'] ?? 0);
        $column_order_raw = (string) ($this->fields_values['column_order'] ?? '');
        $macro_names_raw = (string) ($this->fields_values['macro_names'] ?? '');
        $macro_labels_raw = (string) ($this->fields_values['macro_labels'] ?? '');

        if (!is_array($hostids)) {
            $hostids = [$hostids];
        }

        $hostids = array_values(array_filter($hostids));
        $column_order = $this->parseLines($column_order_raw);
        $macro_names = $this->parseLines($macro_names_raw);
        $macro_labels = $this->parseLines($macro_labels_raw);

        $available_columns = [];
        $available_columns['host'] = ['key' => 'host', 'label' => 'Server'];

        if ($show_cpu) {
            $available_columns['cpu'] = ['key' => 'cpu', 'label' => 'CPU'];
        }
        if ($show_memory) {
            $available_columns['memory'] = ['key' => 'memory', 'label' => 'Memory'];
        }
        if ($show_sessions) {
            $available_columns['sessions'] = ['key' => 'sessions', 'label' => 'Processes'];
        }
        if ($show_cores) {
            $available_columns['cores'] = ['key' => 'cores', 'label' => 'Cores'];
        }
        if ($show_total_memory) {
            $available_columns['total_memory'] = ['key' => 'total_memory', 'label' => 'Total memory'];
        }
        if ($show_disks) {
            $available_columns['disks'] = ['key' => 'disks', 'label' => 'Disks'];
        }
        if ($show_traffic) {
            $available_columns['traffic'] = ['key' => 'traffic', 'label' => 'Traffic'];
        }
        if ($show_uptime) {
            $available_columns['uptime'] = ['key' => 'uptime', 'label' => 'Uptime'];
        }

        foreach ($macro_names as $index => $macro_name) {
            $pretty_label = $macro_labels[$index] ?? $this->prettifyMacroLabel($macro_name);

            $available_columns['macro:' . $macro_name] = [
                'key' => 'macro:' . $macro_name,
                'label' => $pretty_label
            ];
        }

        $columns = [];
        $used = [];

        foreach ($column_order as $column_key) {
            if (isset($available_columns[$column_key])) {
                $columns[] = $available_columns[$column_key];
                $used[$column_key] = true;
            }
        }

        foreach ($available_columns as $column_key => $column) {
            if (!isset($used[$column_key])) {
                $columns[] = $column;
            }
        }

        $rows = [];
        $history_targets = [];

        if ($hostids) {
            $hosts = API::Host()->get([
                'output' => ['hostid', 'host', 'name'],
                'hostids' => $hostids,
                'selectMacros' => ['macro', 'value'],
                'preservekeys' => true
            ]);

            $items = API::Item()->get([
                'output' => ['itemid', 'hostid', 'name', 'key_', 'lastvalue', 'units', 'status', 'state', 'value_type'],
                'hostids' => $hostids,
                'filter' => ['status' => 0],
                'monitored' => true
            ]);

            $items_by_host = [];
            foreach ($items as $item) {
                $items_by_host[$item['hostid']][] = $item;
            }

            foreach ($hostids as $hostid) {
                if (!isset($hosts[$hostid])) {
                    continue;
                }

                $host = $hosts[$hostid];
                $host_items = $items_by_host[$hostid] ?? [];
                $host_macros = $this->indexMacros($host['macros'] ?? []);

                $row = [
                    'hostid' => (string) $hostid,
                    'host' => $host['name'] !== '' ? $host['name'] : $host['host']
                ];

                if ($show_cpu) {
                    $cpu_item = $this->findCpuItem($host_items);
                    $cpu_value = $cpu_item ? $this->normalizePercent($cpu_item['lastvalue']) : null;

                    $row['cpu'] = [
                        'display' => $cpu_item ? $this->formatPercent0($cpu_item['lastvalue']) : 'N/A',
                        'value' => $cpu_value,
                        'key' => $cpu_item['key_'] ?? '',
                        'severity' => $this->percentSeverity($cpu_value),
                        'has_more' => $cpu_item ? 1 : 0,
                        'history' => []
                    ];

                    if ($cpu_item) {
                        $history_targets['cpu:' . $cpu_item['itemid']] = [
                            'itemid' => $cpu_item['itemid'],
                            'value_type' => (int) $cpu_item['value_type'],
                            'field' => 'cpu'
                        ];
                    }
                }

                if ($show_memory) {
                    $mem_item = $this->findMemoryUtilItem($host_items);
                    $mem_value = $mem_item ? $this->normalizePercent($mem_item['lastvalue']) : null;

                    $row['memory'] = [
                        'display' => $mem_item ? $this->formatPercent0($mem_item['lastvalue']) : 'N/A',
                        'value' => $mem_value,
                        'key' => $mem_item['key_'] ?? '',
                        'severity' => $this->percentSeverity($mem_value),
                        'has_more' => $mem_item ? 1 : 0,
                        'history' => []
                    ];

                    if ($mem_item) {
                        $history_targets['memory:' . $mem_item['itemid']] = [
                            'itemid' => $mem_item['itemid'],
                            'value_type' => (int) $mem_item['value_type'],
                            'field' => 'memory'
                        ];
                    }
                }

                if ($show_sessions) {
                    $sessions_item = $this->findSessionsItem($host_items);
                    $row['sessions'] = [
                        'display' => $sessions_item ? $this->formatNumber0($sessions_item['lastvalue']) : 'N/A',
                        'key' => $sessions_item['key_'] ?? '',
                        'history' => []
                    ];

                    if ($sessions_item) {
                        $history_targets['sessions:' . $sessions_item['itemid']] = [
                            'itemid' => $sessions_item['itemid'],
                            'value_type' => (int) $sessions_item['value_type'],
                            'field' => 'sessions'
                        ];
                    }
                }

                if ($show_cores) {
                    $cores_item = $this->findCoresItem($host_items);
                    $row['cores'] = [
                        'display' => $cores_item ? $this->formatNumber0($cores_item['lastvalue']) : 'N/A',
                        'key' => $cores_item['key_'] ?? ''
                    ];
                }

                if ($show_total_memory) {
                    $total_mem_item = $this->findTotalMemoryItem($host_items);
                    $row['total_memory'] = [
                        'display' => $total_mem_item ? $this->formatBytesAutoCompact($total_mem_item['lastvalue'], $total_mem_item['units']) : 'N/A',
                        'key' => $total_mem_item['key_'] ?? ''
                    ];
                }

                if ($show_disks) {
                    $disks = $this->findAllDiskUsageItems($host_items);

                    $row['disks'] = [
                        'display' => $this->buildDisksSummary($disks),
                        'severity' => $this->highestDiskSeverity($disks),
                        'details' => $this->buildDiskDetails($host_items, $disks, (bool) $show_disk_perf)
                    ];
                }

                if ($show_traffic) {
                    $traffic = $this->findTraffic($host_items);
                    $row['traffic'] = [
                        'display' => $traffic['display'],
                        'key' => $traffic['key']
                    ];
                }

                if ($show_uptime) {
                    $uptime_item = $this->findUptimeItem($host_items);
                    $row['uptime'] = [
                        'display' => $uptime_item ? $this->formatUptime($uptime_item['lastvalue']) : 'N/A',
                        'key' => $uptime_item['key_'] ?? ''
                    ];
                }

                foreach ($macro_names as $macro_name) {
                    $row['macro:' . $macro_name] = [
                        'display' => array_key_exists($macro_name, $host_macros) && $host_macros[$macro_name] !== ''
                            ? $host_macros[$macro_name]
                            : 'N/A',
                        'key' => $macro_name
                    ];
                }

                $rows[] = $row;
            }
        }

        $history_map = $this->loadHistoryMap($history_targets, 12);

        foreach ($rows as &$row) {
            foreach (['cpu', 'memory', 'sessions'] as $field) {
                if (!isset($row[$field]['key']) || !isset($row[$field])) {
                    continue;
                }

                foreach ($history_targets as $target) {
                    if ($target['field'] !== $field) {
                        continue;
                    }
                }
            }
        }
        unset($row);

        // attach history by matching item key from host rows
        foreach ($rows as &$row) {
            foreach (['cpu', 'memory', 'sessions'] as $field) {
                if (!isset($row[$field])) {
                    continue;
                }

                $matched_itemid = null;
                foreach ($history_targets as $target) {
                    if ($target['field'] !== $field) {
                        continue;
                    }

                    if (
                        isset($row[$field]['key']) &&
                        $row[$field]['key'] !== '' &&
                        isset($history_map[$target['itemid']])
                    ) {
                        $matched_itemid = $target['itemid'];
                        if (!empty($history_map[$matched_itemid])) {
                            $row[$field]['history'] = $history_map[$matched_itemid];
                            break;
                        }
                    }
                }
            }
        }
        unset($row);

        usort($rows, static function(array $a, array $b): int {
            return strcasecmp($a['host'], $b['host']);
        });

        $this->setResponse(new CControllerResponseData([
            'name' => $this->getInput('name', $this->widget->getDefaultName()),
            'columns' => $columns,
            'rows' => $rows,
            'compact_mode' => $compact_mode,
            'show_disk_perf' => $show_disk_perf,
            'user' => [
                'debug_mode' => $this->getDebugMode()
            ]
        ]));
    }

    private function loadHistoryMap(array $targets, int $limit = 12): array {
        $history_map = [];

        if (!$targets) {
            return $history_map;
        }

        $itemids_by_type = [];
        foreach ($targets as $target) {
            $history_type = $this->mapValueTypeToHistoryType((int) $target['value_type']);
            if ($history_type === null) {
                continue;
            }

            $itemids_by_type[$history_type][] = $target['itemid'];
        }

        foreach ($itemids_by_type as $history_type => $itemids) {
            $records = API::History()->get([
                'output' => ['itemid', 'clock', 'value'],
                'history' => (int) $history_type,
                'itemids' => array_values(array_unique($itemids)),
                'sortfield' => ['clock'],
                'sortorder' => 'DESC',
                'limit' => count(array_unique($itemids)) * $limit
            ]);

            foreach ($records as $record) {
                $itemid = $record['itemid'];

                if (!isset($history_map[$itemid])) {
                    $history_map[$itemid] = [];
                }

                if (count($history_map[$itemid]) >= $limit) {
                    continue;
                }

                $history_map[$itemid][] = [
                    'clock' => (int) $record['clock'],
                    'value' => $record['value']
                ];
            }
        }

        foreach ($history_map as &$points) {
            usort($points, static function(array $a, array $b): int {
                return $a['clock'] <=> $b['clock'];
            });

            foreach ($points as &$point) {
                $point['time'] = date('H:i', $point['clock']);
            }
            unset($point);
        }
        unset($points);

        return $history_map;
    }

    private function mapValueTypeToHistoryType(int $value_type): ?int {
        if ($value_type === 0) {
            return 0; // float
        }

        if ($value_type === 3) {
            return 3; // uint
        }

        return null;
    }

    private function parseLines(string $text): array {
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $result = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $result[] = $line;
            }
        }

        return $result;
    }

    private function prettifyMacroLabel(string $macro_name): string {
        $label = trim($macro_name);
        $label = preg_replace('/^\{\$|\}$/', '', $label);
        $label = preg_replace('/^CMDB_/', '', $label);
        $label = str_replace('_', ' ', $label);
        $label = trim((string) $label);

        if ($label === '') {
            return $macro_name;
        }

        $words = preg_split('/\s+/', strtolower($label));
        $words = array_map(static function($word) {
            $keep_upper = ['os', 'sla', 'cmdb', 'id', 'ip', 'dns', 'url'];
            if (in_array($word, $keep_upper, true)) {
                return strtoupper($word);
            }
            return ucfirst($word);
        }, $words);

        return implode(' ', $words);
    }

    private function indexMacros(array $macros): array {
        $result = [];

        foreach ($macros as $macro) {
            if (isset($macro['macro'])) {
                $result[$macro['macro']] = $macro['value'] ?? '';
            }
        }

        return $result;
    }

    private function findCpuItem(array $items): ?array {
        foreach ($items as $item) {
            $key = $item['key_'] ?? '';
            $name = $item['name'] ?? '';

            if ($key === 'system.cpu.util'
                || strpos($key, 'system.cpu.util[') === 0
                || stripos($name, 'CPU utilization') !== false) {
                return $item;
            }
        }

        return null;
    }

    private function findMemoryUtilItem(array $items): ?array {
        foreach ($items as $item) {
            $key = $item['key_'] ?? '';
            $name = $item['name'] ?? '';

            if ($key === 'vm.memory.util'
                || stripos($name, 'Memory utilization') !== false) {
                return $item;
            }
        }

        return null;
    }

    private function findSessionsItem(array $items): ?array {
        foreach ($items as $item) {
            $key = $item['key_'] ?? '';
            $name = $item['name'] ?? '';

            if ($key === 'proc.num[]'
                || strpos($key, 'proc.num[') === 0
                || stripos($name, 'Number of processes') !== false
                || stripos($name, 'proc.num') !== false) {
                return $item;
            }
        }

        return null;
    }

    private function findCoresItem(array $items): ?array {
        foreach ($items as $item) {
            $key = $item['key_'] ?? '';
            $name = $item['name'] ?? '';

            if ($key === 'system.cpu.num'
                || strpos($key, 'system.cpu.num[') === 0
                || stripos($name, 'Number of CPUs') !== false
                || stripos($name, 'CPU cores') !== false
                || (
                    strpos($key, 'wmi.get[') === 0
                    && (
                        stripos($key, 'NumberOfLogicalProcessors') !== false
                        || stripos($key, 'Win32_ComputerSystem') !== false
                    )
                )
                || (
                    stripos($name, 'logical processors') !== false
                    || stripos($name, 'number of logical processors') !== false
                )) {
                return $item;
            }
        }

        return null;
    }

    private function findTotalMemoryItem(array $items): ?array {
        foreach ($items as $item) {
            $key = $item['key_'] ?? '';
            $name = $item['name'] ?? '';

            if ($key === 'vm.memory.size[total]'
                || stripos($name, 'Total memory') !== false) {
                return $item;
            }
        }

        return null;
    }

    private function findAllDiskUsageItems(array $items): array {
        $result = [];

        foreach ($items as $item) {
            $key = (string) ($item['key_'] ?? '');
            $name = (string) ($item['name'] ?? '');
            $value = $item['lastvalue'] ?? null;

            $disk_name = $this->extractDiskName($item);
            if ($disk_name === '') {
                continue;
            }

            $percent = null;

            if (is_numeric($value) && $this->looksLikePercentDiskItem($key, $name)) {
                $percent = $this->normalizePercent($value);
            }
            else {
                $percent = $this->extractPusedFromJsonItem($name, $value);
            }

            if ($percent === null) {
                continue;
            }

            $result[$disk_name] = [
                'disk' => $disk_name,
                'value' => $percent,
                'display' => $disk_name . ': ' . $this->formatPercent0($percent),
                'item' => $item
            ];
        }

        uasort($result, static function(array $a, array $b): int {
            if ($a['disk'] === $b['disk']) {
                return 0;
            }
            return ($a['disk'] < $b['disk']) ? -1 : 1;
        });

        return array_values($result);
    }

    private function looksLikePercentDiskItem(string $key, string $name): bool {
        if ($key !== '' && strpos($key, 'vfs.fs.size[') === 0 && stripos($key, ',pused') !== false) {
            return true;
        }

        if (preg_match('/^FS\s+\[.*\]:\s+Space Used,\s*in %$/i', $name)) {
            return true;
        }

        if (stripos($name, 'Space Used, in %') !== false && stripos($name, 'FS [') !== false) {
            return true;
        }

        if (stripos($name, 'Space utilization') !== false) {
            return true;
        }

        return false;
    }

    private function extractPusedFromJsonItem(string $name, $value): ?float {
        if (!is_string($value) || $value === '') {
            return null;
        }

        if (stripos($name, ': Get data') === false && stripos($name, 'Get data') === false) {
            return null;
        }

        $json = json_decode($value, true);
        if (is_array($json) && array_key_exists('pused', $json) && is_numeric($json['pused'])) {
            return $this->normalizePercent($json['pused']);
        }

        if (preg_match('/"pused"\s*:\s*([0-9]+(?:\.[0-9]+)?)/i', $value, $m)) {
            return $this->normalizePercent($m[1]);
        }

        return null;
    }

    private function extractDiskName(array $item): string {
        $key = (string) ($item['key_'] ?? '');
        $name = (string) ($item['name'] ?? '');
        $value = (string) ($item['lastvalue'] ?? '');

        if ($key !== '' && preg_match('/vfs\.fs\.size\[([^,]+),/i', $key, $m)) {
            return strtoupper(trim($m[1], "\"' "));
        }

        if (preg_match('/^FS\s+\[(.*?)\]:/i', $name, $m)) {
            $inside = trim($m[1]);

            if (preg_match('/\(([A-Z]:)\)/i', $inside, $m2)) {
                return strtoupper($m2[1]);
            }

            if (preg_match('/^([A-Z]:)$/i', $inside, $m3)) {
                return strtoupper($m3[1]);
            }

            return $inside;
        }

        if (preg_match('/\(([A-Z]:)\)/i', $name, $m)) {
            return strtoupper($m[1]);
        }

        if (preg_match('/([A-Z]:)/i', $name, $m)) {
            return strtoupper($m[1]);
        }

        if ($value !== '') {
            $json = json_decode($value, true);
            if (is_array($json)) {
                if (!empty($json['fsname']) && preg_match('/([A-Z]:)/i', (string) $json['fsname'], $m4)) {
                    return strtoupper($m4[1]);
                }
            }

            if (preg_match('/"fsname"\s*:\s*"([^"]+)"/i', $value, $m6)) {
                if (preg_match('/([A-Z]:)/i', $m6[1], $m7)) {
                    return strtoupper($m7[1]);
                }
            }
        }

        return '';
    }

    private function buildDisksSummary(array $disks): string {
        if (!$disks) {
            return 'N/A';
        }

        $parts = [];
        foreach ($disks as $disk) {
            $parts[] = $disk['display'];
        }

        return implode(' | ', $parts);
    }

    private function highestDiskSeverity(array $disks): string {
        $highest = 'normal';

        foreach ($disks as $disk) {
            $severity = $this->percentSeverity($disk['value']);

            if ($severity === 'critical') {
                return 'critical';
            }

            if ($severity === 'warning') {
                $highest = 'warning';
            }
        }

        return $highest;
    }

    private function buildDiskDetails(array $items, array $disks, bool $show_disk_perf): array {
        $details = [];

        foreach ($disks as $disk) {
            $disk_name = $disk['disk'];
            $json_info = $this->findDiskJsonInfo($items, $disk_name);

            $entry = [
                'disk' => $disk_name,
                'severity' => $this->percentSeverity($disk['value']),
                'summary' => [
                    'Used %' => $this->formatPercent0($disk['value'])
                ],
                'perf' => []
            ];

            if ($json_info !== null) {
                if (isset($json_info['fslabel']) && $json_info['fslabel'] !== '') {
                    $entry['summary']['Label'] = (string) $json_info['fslabel'];
                }
                if (isset($json_info['used']) && is_numeric($json_info['used'])) {
                    $entry['summary']['Used'] = $this->formatBytesAutoCompact($json_info['used'], 'B');
                }
                if (isset($json_info['free']) && is_numeric($json_info['free'])) {
                    $entry['summary']['Free'] = $this->formatBytesAutoCompact($json_info['free'], 'B');
                }
                if (isset($json_info['total']) && is_numeric($json_info['total'])) {
                    $entry['summary']['Total'] = $this->formatBytesAutoCompact($json_info['total'], 'B');
                }
            }

            if ($show_disk_perf) {
                foreach ($items as $item) {
                    $name = (string) ($item['name'] ?? '');
                    $value = $item['lastvalue'] ?? '';
                    $units = (string) ($item['units'] ?? '');

                    if (stripos($name, $disk_name) === false) {
                        continue;
                    }

                    if (stripos($name, 'Disk ') === false) {
                        continue;
                    }

                    $short_name = preg_replace('/^[^:]+:\s*/', '', $name);
                    $short_name = trim((string) $short_name);

                    if ($short_name === '' || stripos($short_name, 'Space Used, in %') !== false) {
                        continue;
                    }

                    $entry['perf'][] = [
                        'name' => $short_name,
                        'value' => $this->formatCompactValue($value, $units)
                    ];
                }
            }

            $details[] = $entry;
        }

        return $details;
    }

    private function findDiskJsonInfo(array $items, string $disk_name): ?array {
        foreach ($items as $item) {
            $name = (string) ($item['name'] ?? '');
            $value = $item['lastvalue'] ?? '';

            if (stripos($name, $disk_name) === false) {
                continue;
            }

            if (stripos($name, 'Get data') === false) {
                continue;
            }

            if (!is_string($value) || $value === '') {
                continue;
            }

            $json = json_decode($value, true);
            if (is_array($json)) {
                return $json;
            }
        }

        return null;
    }

    private function findTraffic(array $items): array {
        $in_total = 0.0;
        $out_total = 0.0;
        $in_found = false;
        $out_found = false;
        $keys = [];

        foreach ($items as $item) {
            $key = $item['key_'] ?? '';
            $name = $item['name'] ?? '';
            $units = strtolower((string) ($item['units'] ?? ''));
            $value = $item['lastvalue'] ?? null;

            if (!is_numeric($value)) {
                continue;
            }

            $is_rate_units = in_array($units, ['bps', 'b', 'b/s', 'bytes/s'], true)
                || str_contains($units, '/s')
                || str_contains($units, 'bps');

            if ((stripos($name, 'Bits received') !== false || stripos($name, 'Inbound traffic') !== false)
                && $is_rate_units) {
                $in_total += (float) $value;
                $in_found = true;
                $keys[] = $key;
                continue;
            }

            if ((stripos($name, 'Bits sent') !== false || stripos($name, 'Outbound traffic') !== false)
                && $is_rate_units) {
                $out_total += (float) $value;
                $out_found = true;
                $keys[] = $key;
                continue;
            }

            if (strpos($key, 'net.if.in[') === 0 && $is_rate_units) {
                $in_total += (float) $value;
                $in_found = true;
                $keys[] = $key;
                continue;
            }

            if (strpos($key, 'net.if.out[') === 0 && $is_rate_units) {
                $out_total += (float) $value;
                $out_found = true;
                $keys[] = $key;
                continue;
            }
        }

        if (!$in_found && !$out_found) {
            return [
                'display' => 'N/A',
                'key' => ''
            ];
        }

        return [
            'display' => 'In '.$this->formatBitsPerSecondCompact($in_total).' / Out '.$this->formatBitsPerSecondCompact($out_total),
            'key' => implode(', ', array_unique($keys))
        ];
    }

    private function findUptimeItem(array $items): ?array {
        foreach ($items as $item) {
            $key = $item['key_'] ?? '';
            $name = $item['name'] ?? '';

            if ($key === 'system.uptime'
                || stripos($name, 'uptime') !== false) {
                return $item;
            }
        }

        return null;
    }

    private function normalizePercent($value): ?float {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        $value = (float) $value;

        if ($value < 0) {
            $value = 0;
        }

        if ($value > 100) {
            $value = 100;
        }

        return round($value, 2);
    }

    private function percentSeverity($value): string {
        if ($value === null) {
            return 'normal';
        }

        if ($value >= 90) {
            return 'critical';
        }

        if ($value >= 70) {
            return 'warning';
        }

        return 'normal';
    }

    private function formatPercent0($value): string {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return 'N/A';
        }

        return round((float) $value, 0).'%';
    }

    private function formatNumber0($value): string {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return 'N/A';
        }

        return (string) round((float) $value, 0);
    }

    private function formatCompactValue($value, string $units = ''): string {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return 'N/A';
        }

        $num = (float) $value;
        $units = trim($units);

        if ($units === '%' || stripos($units, '%') !== false) {
            return round($num, 0).' %';
        }

        if ($units !== '') {
            return round($num, 0).' '.$units;
        }

        return (string) round($num, 0);
    }

    private function formatBytesAutoCompact($value, string $units = ''): string {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return 'N/A';
        }

        $bytes = (float) $value;
        $units = strtoupper(trim($units));

        if ($units === 'B' || $units === 'BPS' || $units === '') {
            $size_units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $i = 0;

            while ($bytes >= 1024 && $i < count($size_units) - 1) {
                $bytes /= 1024;
                $i++;
            }

            return round($bytes, 0).' '.$size_units[$i];
        }

        return round((float) $value, 0).' '.$units;
    }

    private function formatUptime($value): string {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return 'N/A';
        }

        $seconds = (int) $value;
        $days = intdiv($seconds, 86400);
        $seconds %= 86400;
        $hours = intdiv($seconds, 3600);
        $seconds %= 3600;
        $minutes = intdiv($seconds, 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days.'d';
        }
        if ($hours > 0 || $days > 0) {
            $parts[] = $hours.'h';
        }
        $parts[] = $minutes.'m';

        return implode(' ', $parts);
    }

    private function formatBitsPerSecondCompact(float $value): string {
        $units = ['bps', 'Kbps', 'Mbps', 'Gbps', 'Tbps'];
        $i = 0;

        while ($value >= 1000 && $i < count($units) - 1) {
            $value /= 1000;
            $i++;
        }

        return round($value, 1).' '.$units[$i];
    }
}
