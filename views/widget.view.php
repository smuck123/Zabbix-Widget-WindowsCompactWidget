<?php

$columns = $data['columns'] ?? [];
$rows = $data['rows'] ?? [];
$compact_mode = !empty($data['compact_mode']);
$show_disk_perf = !empty($data['show_disk_perf']);

function wcw_render_text_cell(array $cell, string $class = ''): string {
    $display = $cell['display'] ?? 'N/A';
    $key = htmlspecialchars($cell['key'] ?? '');
    $class_attr = $class !== '' ? ' class="'.$class.'"' : '';
    $history_json = !empty($cell['history']) ? htmlspecialchars(json_encode($cell['history']), ENT_QUOTES) : '';
    $history_label = htmlspecialchars($cell['key'] ?? 'Metric');
    $history_attr = $history_json !== ''
        ? ' data-history="'.$history_json.'" data-history-label="'.$history_label.'"'
        : '';

    if ($display === 'N/A') {
        return '<td'.$class_attr.' title="'.$key.'"><span class="wcw-na">N/A</span></td>';
    }

    return '<td'.$class_attr.' title="'.htmlspecialchars($display).'">
        <div class="wcw-text-cell"'.$history_attr.'>'.nl2br(htmlspecialchars($display)).'</div>
    </td>';
}

function wcw_render_percent_cell(array $cell, bool $compact_mode = true): string {
    $display = $cell['display'] ?? 'N/A';
    $value = $cell['value'] ?? null;
    $key = htmlspecialchars($cell['key'] ?? '');
    $severity = $cell['severity'] ?? 'normal';
    $has_more = !empty($cell['has_more']);
    $history_json = !empty($cell['history']) ? htmlspecialchars(json_encode($cell['history']), ENT_QUOTES) : '';
    $history_label = htmlspecialchars($cell['key'] ?? 'Metric');
    $history_attr = $history_json !== ''
        ? ' data-history="'.$history_json.'" data-history-label="'.$history_label.'"'
        : '';

    if ($value === null) {
        return '<td class="wcw-metric-cell" title="'.$key.'"><span class="wcw-na">N/A</span></td>';
    }

    $bar_class = 'wcw-bar';
    if ($severity === 'warning') {
        $bar_class .= ' warning';
    }
    elseif ($severity === 'critical') {
        $bar_class .= ' critical';
    }

    $marker = $has_more ? '<span class="wcw-more-marker" title="More performance details available">•</span>' : '';

    if ($compact_mode) {
        return '<td class="wcw-metric-cell" title="'.$key.'">
            <div class="wcw-metric wcw-metric-compact">
                <div class="wcw-bar-wrap">
                    <div class="'.$bar_class.'" style="width: '.(float) $value.'%;"></div>
                </div>
                <div class="wcw-metric-value"'.$history_attr.'>'.$marker.htmlspecialchars($display).'</div>
            </div>
        </td>';
    }

    return '<td class="wcw-metric-cell" title="'.$key.'">
        <div class="wcw-metric">
            <div class="wcw-bar-wrap">
                <div class="'.$bar_class.'" style="width: '.(float) $value.'%;"></div>
            </div>
            <div class="wcw-metric-value"'.$history_attr.'>'.$marker.htmlspecialchars($display).'</div>
        </div>
    </td>';
}

function wcw_build_disk_summary(array $cell): string {
    $details = $cell['details'] ?? [];

    if (!$details) {
        return 'No disks';
    }

    $count = count($details);
    $worst = null;

    foreach ($details as $disk) {
        if (!isset($disk['summary']['Used %'])) {
            continue;
        }

        $used = (string) $disk['summary']['Used %'];
        if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $used, $m)) {
            $value = (float) $m[1];
            if ($worst === null || $value > $worst) {
                $worst = $value;
            }
        }
    }

    if ($worst !== null) {
        return $count.'d · '.round($worst, 0).'%';
    }

    return (string) $count.'d';
}

function wcw_render_disk_cell(array $cell, bool $show_disk_perf = false): string {
    $display = $cell['display'] ?? 'N/A';
    $severity = $cell['severity'] ?? 'normal';
    $details = $cell['details'] ?? [];

    if ($display === 'N/A') {
        return '<td class="wcw-disk-cell"><span class="wcw-na">N/A</span></td>';
    }

    $summary_class = 'wcw-disk-summary';
    if ($severity === 'warning') {
        $summary_class .= ' warning';
    }
    elseif ($severity === 'critical') {
        $summary_class .= ' critical';
    }

    $compact_summary = wcw_build_disk_summary($cell);

    $html = '<td class="wcw-disk-cell">';
    $html .= '<details class="wcw-disk-details">';
    $html .= '<summary class="'.$summary_class.'" title="'.htmlspecialchars($display).'">';
    $html .= '<span class="wcw-disk-summary-main">'.htmlspecialchars($compact_summary).'</span>';
    $html .= '<span class="wcw-disk-summary-sub">click for detail</span>';
    $html .= '</summary>';
    $html .= '<div class="wcw-disk-panel">';

    foreach ($details as $disk) {
        $disk_class = 'wcw-disk-card';
        if (($disk['severity'] ?? 'normal') === 'warning') {
            $disk_class .= ' warning';
        }
        elseif (($disk['severity'] ?? 'normal') === 'critical') {
            $disk_class .= ' critical';
        }

        $html .= '<div class="'.$disk_class.'">';
        $html .= '<div class="wcw-disk-card-title">'.htmlspecialchars($disk['disk'] ?? 'Disk').'</div>';

        $html .= '<div class="wcw-disk-meta">';
        foreach (($disk['summary'] ?? []) as $label => $value) {
            $html .= '<div class="wcw-disk-meta-row">';
            $html .= '<span class="wcw-disk-meta-label">'.htmlspecialchars((string) $label).'</span>';
            $html .= '<span class="wcw-disk-meta-value">'.htmlspecialchars((string) $value).'</span>';
            $html .= '</div>';
        }
        $html .= '</div>';

        if ($show_disk_perf && !empty($disk['perf'])) {
            $html .= '<div class="wcw-disk-perf-title">Performance</div>';
            $html .= '<table class="wcw-disk-perf-table"><tbody>';
            foreach ($disk['perf'] as $perf) {
                $html .= '<tr>';
                $history_json = !empty($perf['history']) ? htmlspecialchars(json_encode($perf['history']), ENT_QUOTES) : '';
                $history_label = htmlspecialchars(($disk['disk'] ?? 'Disk').' · '.($perf['name'] ?? 'Performance'));
                $history_attr = $history_json !== ''
                    ? ' data-history="'.$history_json.'" data-history-label="'.$history_label.'"'
                    : '';

                $html .= '<td>'.htmlspecialchars($perf['name'] ?? '').'</td>';
                $html .= '<td class="wcw-clickable-value"'.$history_attr.'>'.htmlspecialchars($perf['value'] ?? '').'</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }

        $html .= '</div>';
    }

    $html .= '</div>';
    $html .= '</details>';
    $html .= '</td>';

    return $html;
}

ob_start();
?>
<style>
.windows-compact-widget {
    padding: 6px;
    font-size: 12px;
}
.windows-compact-widget table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
.windows-compact-widget th,
.windows-compact-widget td {
    padding: 6px 8px;
    border-bottom: 1px solid #dfe3ea;
    text-align: left;
    vertical-align: top;
}
.windows-compact-widget th {
    font-weight: 600;
    white-space: nowrap;
}
.windows-compact-widget .host-cell {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 110px;
    max-width: 160px;
}
.wcw-text-cell {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.wcw-metric-cell {
    min-width: 78px;
    width: 78px;
}
.wcw-metric {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.wcw-metric-compact {
    gap: 3px;
}
.wcw-bar-wrap {
    position: relative;
    width: 100%;
    height: 12px;
    background: #eef2f7;
    border-radius: 999px;
    overflow: hidden;
}
.wcw-bar {
    height: 12px;
    border-radius: 999px;
    background: linear-gradient(90deg, #3b82f6, #2563eb);
}
.wcw-bar.warning {
    background: linear-gradient(90deg, #f59e0b, #d97706);
}
.wcw-bar.critical {
    background: linear-gradient(90deg, #ef4444, #dc2626);
}
.wcw-metric-value {
    font-size: 10px;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
}
.wcw-clickable-value,
.wcw-metric-value[data-history],
.wcw-text-cell[data-history] {
    cursor: pointer;
    text-decoration: underline dotted;
    text-underline-offset: 2px;
}
.wcw-more-marker {
    display: inline-block;
    margin-right: 3px;
    color: #9ecbff;
    font-size: 11px;
    vertical-align: middle;
}
.wcw-na {
    color: #9ca3af;
    font-style: italic;
}
.wcw-disk-cell {
    min-width: 90px;
    width: 90px;
}
.wcw-disk-details {
    display: block;
}
.wcw-disk-details summary {
    list-style: none;
    cursor: pointer;
}
.wcw-disk-details summary::-webkit-details-marker {
    display: none;
}
.wcw-disk-summary {
    display: block;
    width: 100%;
    padding: 5px 6px;
    border-radius: 8px;
    background: #f0f6ff;
    color: #1d4f91;
    box-sizing: border-box;
    border: 1px solid #b7d4ff;
}
.wcw-disk-summary.warning {
    background: #fff4d6;
    color: #8a5a00;
    border-color: #f0c36d;
}
.wcw-disk-summary.critical {
    background: #fde2e2;
    color: #a61b1b;
    border-color: #f0aaaa;
}
.wcw-disk-summary-main {
    display: block;
    font-size: 10px;
    font-weight: 700;
    line-height: 1.2;
}
.wcw-disk-summary-sub {
    display: block;
    margin-top: 2px;
    font-size: 9px;
    opacity: 0.85;
    line-height: 1.1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.wcw-disk-panel {
    margin-top: 8px;
    display: grid;
    gap: 10px;
}
.wcw-disk-card {
    border: 1px solid #3b4450;
    border-radius: 10px;
    background: #1f232a;
    color: #e6edf3;
    padding: 10px;
    min-width: 0;
}
.wcw-disk-card.warning {
    border-color: #d99a25;
}
.wcw-disk-card.critical {
    border-color: #d9534f;
}
.wcw-disk-card-title {
    font-weight: 700;
    margin-bottom: 8px;
    color: #9ecbff;
    font-size: 12px;
}
.wcw-disk-meta {
    display: grid;
    gap: 4px;
    margin-bottom: 8px;
}
.wcw-disk-meta-row {
    display: grid;
    grid-template-columns: 70px 1fr;
    gap: 8px;
    align-items: start;
    border-bottom: 1px solid #353d48;
    padding-bottom: 3px;
}
.wcw-disk-meta-label {
    color: #aab6c3;
    font-weight: 600;
    font-size: 10px;
}
.wcw-disk-meta-value {
    font-weight: 700;
    text-align: right;
    white-space: nowrap;
    font-size: 10px;
}
.wcw-disk-perf-title {
    margin-top: 8px;
    margin-bottom: 4px;
    font-weight: 700;
    color: #9ecbff;
    font-size: 11px;
}
.wcw-disk-perf-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: auto;
}
.wcw-disk-perf-table td {
    border-bottom: 1px solid #353d48;
    padding: 4px 4px;
    font-size: 10px;
    vertical-align: top;
}
.wcw-disk-perf-table td:first-child {
    width: 68%;
    color: #d4dde7;
    word-break: break-word;
}
.wcw-disk-perf-table td:last-child {
    width: 32%;
    text-align: right;
    font-weight: 600;
    white-space: nowrap;
}
.wcw-history-popup {
    position: fixed;
    z-index: 10000;
    width: 280px;
    background: #eef3f9;
    border: 1px solid #cad6e5;
    border-radius: 8px;
    box-shadow: 0 8px 28px rgba(16, 24, 40, 0.18);
    padding: 8px;
    color: #1f2937;
}
.wcw-history-popup.hidden {
    display: none;
}
.wcw-history-popup-title {
    font-size: 11px;
    font-weight: 700;
    margin-bottom: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.wcw-history-popup-stats {
    font-size: 10px;
    color: #374151;
    margin-top: 6px;
}
.wcw-history-popup-time {
    font-size: 10px;
    color: #475569;
    margin-top: 2px;
}
</style>

<div class="windows-compact-widget">
<?php if (!$rows): ?>
    <div class="wcw-empty">No hosts selected or no data found.</div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <?php foreach ($columns as $column): ?>
                    <th><?= htmlspecialchars($column['label']) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($columns as $column): ?>
                        <?php $col_key = $column['key']; ?>

                        <?php if ($col_key === 'host'): ?>
                            <td class="host-cell" title="<?= htmlspecialchars($row['host']) ?>">
                                <?= htmlspecialchars($row['host']) ?>
                            </td>
                        <?php elseif ($col_key === 'cpu' && isset($row['cpu'])): ?>
                            <?= wcw_render_percent_cell($row['cpu'], $compact_mode) ?>
                        <?php elseif ($col_key === 'memory' && isset($row['memory'])): ?>
                            <?= wcw_render_percent_cell($row['memory'], $compact_mode) ?>
                        <?php elseif ($col_key === 'sessions' && isset($row['sessions'])): ?>
                            <?= wcw_render_text_cell($row['sessions']) ?>
                        <?php elseif ($col_key === 'cores' && isset($row['cores'])): ?>
                            <?= wcw_render_text_cell($row['cores']) ?>
                        <?php elseif ($col_key === 'total_memory' && isset($row['total_memory'])): ?>
                            <?= wcw_render_text_cell($row['total_memory']) ?>
                        <?php elseif ($col_key === 'disks' && isset($row['disks'])): ?>
                            <?= wcw_render_disk_cell($row['disks'], $show_disk_perf) ?>
                        <?php elseif ($col_key === 'traffic' && isset($row['traffic'])): ?>
                            <?= wcw_render_text_cell($row['traffic']) ?>
                        <?php elseif ($col_key === 'uptime' && isset($row['uptime'])): ?>
                            <?= wcw_render_text_cell($row['uptime']) ?>
                        <?php elseif (strpos($col_key, 'macro:') === 0 && isset($row[$col_key])): ?>
                            <?= wcw_render_text_cell($row[$col_key]) ?>
                        <?php else: ?>
                            <td><span class="wcw-na">N/A</span></td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>

<div class="wcw-history-popup hidden">
    <div class="wcw-history-popup-title"></div>
    <svg width="260" height="74" viewBox="0 0 260 74" xmlns="http://www.w3.org/2000/svg">
        <polyline fill="none" stroke="#2563eb" stroke-width="2" points=""></polyline>
    </svg>
    <div class="wcw-history-popup-stats"></div>
    <div class="wcw-history-popup-time"></div>
</div>

<script>
(function() {
    const root = document.currentScript.closest('.windows-compact-widget') || document;
    const popup = document.currentScript.parentElement.querySelector('.wcw-history-popup');

    if (!popup) {
        return;
    }

    const titleNode = popup.querySelector('.wcw-history-popup-title');
    const statsNode = popup.querySelector('.wcw-history-popup-stats');
    const timeNode = popup.querySelector('.wcw-history-popup-time');
    const polyline = popup.querySelector('polyline');

    const hidePopup = () => popup.classList.add('hidden');

    const renderSparkline = (points) => {
        if (!points.length) {
            polyline.setAttribute('points', '');
            return;
        }

        const values = points.map(p => Number(p.value)).filter(v => Number.isFinite(v));
        if (!values.length) {
            polyline.setAttribute('points', '');
            return;
        }

        const min = Math.min(...values);
        const max = Math.max(...values);
        const range = Math.max(max - min, 0.0001);
        const width = 260;
        const height = 74;
        const stepX = values.length > 1 ? width / (values.length - 1) : width;

        const svgPoints = values.map((v, idx) => {
            const x = Math.round(idx * stepX);
            const y = Math.round((1 - (v - min) / range) * (height - 8) + 4);
            return `${x},${y}`;
        }).join(' ');

        polyline.setAttribute('points', svgPoints);

        const last = values[values.length - 1];
        statsNode.textContent = `Min: ${min.toFixed(2)} | Max: ${max.toFixed(2)} | Last: ${last.toFixed(2)}`;
        timeNode.textContent = `Points: ${values.length}`;
    };

    root.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-history]');
        if (!trigger) {
            hidePopup();
            return;
        }

        let points = [];
        try {
            points = JSON.parse(trigger.getAttribute('data-history') || '[]');
        }
        catch (e) {
            hidePopup();
            return;
        }

        if (!Array.isArray(points) || points.length === 0) {
            hidePopup();
            return;
        }

        titleNode.textContent = trigger.getAttribute('data-history-label') || 'Metric history';
        renderSparkline(points);

        const rect = trigger.getBoundingClientRect();
        popup.style.left = `${Math.min(window.innerWidth - 290, Math.max(8, rect.left - 4))}px`;
        popup.style.top = `${Math.min(window.innerHeight - 130, rect.bottom + 6)}px`;
        popup.classList.remove('hidden');
        event.stopPropagation();
    });

    document.addEventListener('click', (event) => {
        if (!popup.contains(event.target)) {
            hidePopup();
        }
    });
})();
</script>
<?php
$html = ob_get_clean();

(new CWidgetView($data))
    ->addItem($html)
    ->show();
