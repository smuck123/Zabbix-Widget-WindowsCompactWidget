<?php

(new CWidgetFormView($data))
    ->addField(new CWidgetFieldMultiSelectHostView($data['fields']['hostids']))
    ->addField(new CWidgetFieldCheckBoxView($data['fields']['show_cpu']))
    ->addField(new CWidgetFieldCheckBoxView($data['fields']['show_memory']))
    ->addField(new CWidgetFieldCheckBoxView($data['fields']['show_sessions']))
    ->addField(new CWidgetFieldCheckBoxView($data['fields']['show_cores']))
    ->addField(new CWidgetFieldCheckBoxView($data['fields']['show_total_memory']))
    ->addField(new CWidgetFieldCheckBoxView($data['fields']['show_disks']))
    ->addField(new CWidgetFieldCheckBoxView($data['fields']['show_traffic']))
    ->addField(new CWidgetFieldCheckBoxView($data['fields']['show_uptime']))
    ->addField(new CWidgetFieldCheckBoxView($data['fields']['compact_mode']))
    ->addField(new CWidgetFieldCheckBoxView($data['fields']['show_disk_perf']))
    ->addField(new CWidgetFieldTextAreaView($data['fields']['column_order']))
    ->addField(new CWidgetFieldTextAreaView($data['fields']['macro_names']))
    ->addField(new CWidgetFieldTextAreaView($data['fields']['macro_labels']))
    ->show();
