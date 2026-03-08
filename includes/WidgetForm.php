<?php

namespace Modules\WindowsCompactWidget\Includes;

use Zabbix\Widgets\CWidgetForm;
use Zabbix\Widgets\CWidgetField;
use Zabbix\Widgets\Fields\CWidgetFieldMultiSelectHost;
use Zabbix\Widgets\Fields\CWidgetFieldCheckBox;
use Zabbix\Widgets\Fields\CWidgetFieldTextArea;

class WidgetForm extends CWidgetForm {

    public function addFields(): self {
        return $this
            ->addField(
                (new CWidgetFieldMultiSelectHost('hostids', _('Windows Servers')))
                    ->setFlags(CWidgetField::FLAG_LABEL_ASTERISK)
            )
            ->addField(
                (new CWidgetFieldCheckBox('show_cpu', _('Show CPU')))
                    ->setDefault(1)
            )
            ->addField(
                (new CWidgetFieldCheckBox('show_memory', _('Show Memory')))
                    ->setDefault(1)
            )
            ->addField(
                (new CWidgetFieldCheckBox('show_sessions', _('Show Processes')))
                    ->setDefault(1)
            )
            ->addField(
                (new CWidgetFieldCheckBox('show_cores', _('Show CPU cores')))
                    ->setDefault(1)
            )
            ->addField(
                (new CWidgetFieldCheckBox('show_total_memory', _('Show Total memory')))
                    ->setDefault(1)
            )
            ->addField(
                (new CWidgetFieldCheckBox('show_disks', _('Show Disks')))
                    ->setDefault(1)
            )
            ->addField(
                (new CWidgetFieldCheckBox('show_traffic', _('Show Traffic')))
                    ->setDefault(1)
            )
            ->addField(
                (new CWidgetFieldCheckBox('show_uptime', _('Show Uptime')))
                    ->setDefault(1)
            )
            ->addField(
                (new CWidgetFieldCheckBox('compact_mode', _('Compact mode')))
                    ->setDefault(1)
            )
            ->addField(
                (new CWidgetFieldCheckBox('show_disk_perf', _('Show disk performance rows')))
                    ->setDefault(0)
            )
            ->addField(
                (new CWidgetFieldTextArea('column_order', _('Column order (one key per line)')))
                    ->setDefault("host\ncpu\nmemory\nsessions\ncores\ntotal_memory\ndisks\ntraffic\nuptime")
            )
            ->addField(
                (new CWidgetFieldTextArea('macro_names', _('Macros to show (one per line)')))
                    ->setDefault("{\$CMDB_NOTES}\n{\$CMDB_OS}\n{\$CMDB_OWNER}\n{\$CMDB_SLA}")
            )
            ->addField(
                (new CWidgetFieldTextArea('macro_labels', _('Macro labels (one per line, same order)')))
                    ->setDefault("Notes\nOS\nOwner\nSLA")
            );
    }
}
