<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Mobile app screens.
 *
 * The templates are static: all user and field data is passed in "otherdata" and shown with
 * Angular bindings, so nothing typed by users is ever compiled as template code.
 *
 * Screens: user search (main), edit one user, bulk update, recent changes.
 * File import / Excel template / Columns / Branding stay on the website.
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_profileeditor\output;

defined('MOODLE_INTERNAL') || die();

use local_profileeditor\branding;
use local_profileeditor\field_manager;
use local_profileeditor\user_query;

/**
 * Mobile output.
 */
class mobile {

    /** @var int Users per page in the app. */
    const PERPAGE = 30;

    /**
     * Only show the app entries to people who can use the editor.
     *
     * @param array $args
     * @return array
     */
    public static function mobile_init($args) {
        $can = isloggedin() && !isguestuser() &&
            has_capability('local/profileeditor:view', \context_system::instance());
        return ['templates' => [], 'javascript' => '', 'disabled' => !$can];
    }

    /**
     * Language string, escaped for a template (no binding braces).
     *
     * @param string $id
     * @param mixed $a
     * @return string
     */
    protected static function str(string $id, $a = null): string {
        $s = get_string($id, 'local_profileeditor', $a);
        return str_replace(['{', '}'], ['&#123;', '&#125;'], s(strip_tags($s)));
    }

    /**
     * Core string, escaped.
     *
     * @param string $id
     * @return string
     */
    protected static function core(string $id): string {
        return str_replace(['{', '}'], ['&#123;', '&#125;'], s(get_string($id)));
    }

    /**
     * Branded header band + navigation buttons. The title comes from CONTENT_OTHERDATA.title.
     *
     * @param string $current
     * @return string
     */
    protected static function header(string $current): string {
        $context = \context_system::instance();
        $nav = '';
        $items = ['view' => ['mobile_view', 'mobile_nav_users'], 'bulk' => ['mobile_bulk', 'mobile_nav_bulk'],
            'log' => ['mobile_log', 'mobile_nav_log']];
        foreach ($items as $key => [$method, $str]) {
            if ($key === $current || ($key === 'bulk' && !has_capability('local/profileeditor:edit', $context)) ||
                    ($key === 'log' && !has_capability('local/profileeditor:viewlog', $context))) {
                continue;
            }
            $nav .= '<ion-button size="small" fill="outline" [ngStyle]="{\'--color\': CONTENT_OTHERDATA.brand.headertext, ' .
                '\'--border-color\': CONTENT_OTHERDATA.brand.accent}" core-site-plugins-new-content ' .
                'component="local_profileeditor" method="' . $method . '" title="' . self::str($str) . '">' .
                self::str($str) . '</ion-button>';
        }
        return '<div class="ion-padding" [ngStyle]="{\'background\': CONTENT_OTHERDATA.brand.headerbg, ' .
            '\'color\': CONTENT_OTHERDATA.brand.headertext, \'border-radius\': CONTENT_OTHERDATA.brand.radius, ' .
            '\'margin\': \'12px\', \'border-bottom\': \'4px solid \' + CONTENT_OTHERDATA.brand.accent}">' .
            '<div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.85">' .
            '{{ CONTENT_OTHERDATA.brand.org }}</div>' .
            '<h2 style="margin:4px 0 8px;font-weight:700" [ngStyle]="{\'color\': CONTENT_OTHERDATA.brand.headertext}">' .
            '{{ CONTENT_OTHERDATA.title }}</h2>' . ($nav ? '<div>' . $nav . '</div>' : '') . '</div>';
    }

    /**
     * Branding values for the templates.
     *
     * @return string JSON
     */
    protected static function brand(): string {
        $b = branding::get();
        return json_encode([
            'org' => branding::org_name(),
            'headerbg' => $b['headerbg'],
            'headertext' => $b['headertext'],
            'primary' => $b['primary'],
            'primarytext' => $b['primarytext'],
            'accent' => $b['accent'],
            'muted' => $b['muted'],
            'radius' => $b['radius'] . 'px',
        ]);
    }

    /**
     * Primary button style binding.
     *
     * @return string
     */
    protected static function btnstyle(): string {
        return '[ngStyle]="{\'--background\': CONTENT_OTHERDATA.brand.primary, \'--color\': CONTENT_OTHERDATA.brand.primarytext}"';
    }

    /**
     * No permission page.
     *
     * @return array
     */
    protected static function nopermission(): array {
        return ['templates' => [['id' => 'main', 'html' =>
            '<div class="ion-padding"><p>' . self::core('nopermissions') . '</p></div>']]];
    }

    /**
     * Main screen: search users.
     *
     * @param array $args
     * @return array
     */
    public static function mobile_view($args) {
        global $DB;
        $context = \context_system::instance();
        if (!has_capability('local/profileeditor:view', $context)) {
            return self::nopermission();
        }
        $search = trim((string) ($args['search'] ?? ''));
        $page = max(0, (int) ($args['page'] ?? 0));

        [$where, $params, $orderby] = user_query::build(['search' => $search, 'filterfield' => '', 'filtervalue' => '',
            'filterempty' => 0, 'showsuspended' => 1, 'sort' => 'core_lastname', 'dir' => 'ASC']);
        $total = $DB->count_records_sql("SELECT COUNT(1) FROM {user} u WHERE $where", $params);
        $recs = $DB->get_records_sql("SELECT u.id, u.username, u.email, u.firstname, u.lastname, u.department, u.suspended
                                        FROM {user} u WHERE $where ORDER BY $orderby", $params,
            $page * self::PERPAGE, self::PERPAGE);
        $users = [];
        foreach ($recs as $u) {
            $users[] = ['id' => (int) $u->id, 'name' => trim($u->firstname . ' ' . $u->lastname) ?: $u->username,
                'sub' => $u->username . ($u->email ? ' · ' . $u->email : ''), 'department' => (string) $u->department,
                'suspended' => (bool) $u->suspended];
        }

        $html = self::header('view') .
            '<ion-list>' .
            '<ion-item>' .
            '<ion-input [(ngModel)]="CONTENT_OTHERDATA.search" type="search" [clearInput]="true" ' .
            'placeholder="' . self::str('searchplaceholder') . '" aria-label="' . self::str('search') . '" ' .
            '(keyup.enter)="updateContent({search: CONTENT_OTHERDATA.search, page: 0})"></ion-input>' .
            '<ion-button slot="end" ' . self::btnstyle() .
            ' (click)="updateContent({search: CONTENT_OTHERDATA.search, page: 0})">' . self::str('search') . '</ion-button>' .
            '</ion-item>' .
            '<ion-item lines="none"><ion-label><p>' . self::str('mobile_found') .
            ' {{ CONTENT_OTHERDATA.total }}</p></ion-label></ion-item>' .
            '<ion-item *ngFor="let u of CONTENT_OTHERDATA.users" button [detail]="true" ' .
            'core-site-plugins-new-content component="local_profileeditor" method="mobile_user" ' .
            '[args]="{targetuserid: u.id}" [title]="u.name">' .
            '<ion-label><p class="item-heading">{{ u.name }}' .
            '<span *ngIf="u.suspended" style="opacity:.6"> (' . self::core('suspended') . ')</span></p>' .
            '<p>{{ u.sub }}</p><p *ngIf="u.department">{{ u.department }}</p></ion-label></ion-item>' .
            '</ion-list>' .
            '<div class="ion-padding" style="display:flex;gap:8px;justify-content:space-between">' .
            '<ion-button fill="outline" *ngIf="CONTENT_OTHERDATA.page > 0" ' .
            '(click)="updateContent({search: CONTENT_OTHERDATA.search, page: CONTENT_OTHERDATA.page - 1})">' .
            self::core('previous') . '</ion-button>' .
            '<ion-button fill="outline" *ngIf="CONTENT_OTHERDATA.hasmore" ' .
            '(click)="updateContent({search: CONTENT_OTHERDATA.search, page: CONTENT_OTHERDATA.page + 1})">' .
            self::core('next') . '</ion-button></div>' .
            '<p class="ion-padding-horizontal" style="font-size:13px;opacity:.7">' . self::str('mobile_webonly') . '</p>';

        return [
            'templates' => [['id' => 'main', 'html' => $html]],
            'otherdata' => [
                'brand' => self::brand(),
                'title' => get_string('pluginname', 'local_profileeditor'),
                'search' => $search,
                'page' => $page,
                'total' => $total,
                'hasmore' => ($page + 1) * self::PERPAGE < $total ? 1 : 0,
                'users' => json_encode($users),
            ],
        ];
    }

    /**
     * Edit one user.
     *
     * @param array $args targetuserid (from the list) or userid (from a profile)
     * @return array
     */
    public static function mobile_user($args) {
        $context = \context_system::instance();
        if (!has_capability('local/profileeditor:view', $context)) {
            return self::nopermission();
        }
        $userid = (int) ($args['targetuserid'] ?? ($args['userid'] ?? 0));
        $user = \core_user::get_user($userid);
        if (!$user || $user->deleted || isguestuser($user)) {
            return ['templates' => [['id' => 'main', 'html' => '<div class="ion-padding">' .
                self::core('invaliduser') . '</div>']]];
        }
        $canedit = has_capability('local/profileeditor:edit', $context) && (!is_siteadmin($user) || is_siteadmin());

        $fields = field_manager::get_visible_fields();
        $raw = field_manager::get_values([$user->id => $user], $fields)[$user->id] ?? [];
        $list = [];
        $values = [];
        foreach ($fields as $f) {
            $editable = $canedit && field_manager::is_editable($f);
            $r = (string) ($raw[$f->key] ?? '');
            $list[] = ['key' => $f->key, 'name' => $f->name, 'category' => $f->category, 'type' => $f->datatype,
                'options' => $f->options, 'editable' => $editable, 'display' => field_manager::format_value($f, $r)];
            if ($editable) {
                $values[$f->key] = field_manager::input_value($f, $r);
            }
        }

        $label = '[label]="f.name" labelPlacement="stacked"';
        $html = self::header('user') .
            '<p class="ion-padding-horizontal" style="margin-top:0">{{ CONTENT_OTHERDATA.sub }}</p>' .
            '<ion-list>' .
            '<ng-container *ngFor="let f of CONTENT_OTHERDATA.fields; let i = index">' .
            '<ion-item-divider *ngIf="i === 0 || f.category !== CONTENT_OTHERDATA.fields[i - 1].category">' .
            '<ion-label>{{ f.category }}</ion-label></ion-item-divider>' .
            // Read-only.
            '<ion-item *ngIf="!f.editable"><ion-label><p class="item-heading">{{ f.name }}</p>' .
            '<p>{{ f.display || \'—\' }}</p></ion-label></ion-item>' .
            // Drop-down.
            '<ion-item *ngIf="f.editable && f.type === \'menu\'"><ion-select ' . $label .
            ' [(ngModel)]="CONTENT_OTHERDATA.values[f.key]" interface="action-sheet" cancelText="' .
            self::core('cancel') . '"><ion-select-option value="">—</ion-select-option>' .
            '<ion-select-option *ngFor="let o of f.options" [value]="o">{{ o }}</ion-select-option></ion-select></ion-item>' .
            // Yes / no.
            '<ion-item *ngIf="f.editable && f.type === \'checkbox\'"><ion-select ' . $label .
            ' [(ngModel)]="CONTENT_OTHERDATA.values[f.key]" interface="popover">' .
            '<ion-select-option value="1">' . self::core('yes') . '</ion-select-option>' .
            '<ion-select-option value="0">' . self::core('no') . '</ion-select-option></ion-select></ion-item>' .
            // Date.
            '<ion-item *ngIf="f.editable && f.type === \'datetime\'"><ion-input type="date" ' . $label .
            ' [(ngModel)]="CONTENT_OTHERDATA.values[f.key]"></ion-input></ion-item>' .
            // Long text.
            '<ion-item *ngIf="f.editable && f.type === \'textarea\'"><ion-textarea ' . $label .
            ' [autoGrow]="true" [(ngModel)]="CONTENT_OTHERDATA.values[f.key]"></ion-textarea></ion-item>' .
            // Text / email.
            '<ion-item *ngIf="f.editable && f.type !== \'menu\' && f.type !== \'checkbox\' && f.type !== \'datetime\' ' .
            '&& f.type !== \'textarea\'"><ion-input ' . $label . ' [type]="f.type === \'email\' ? \'email\' : \'text\'" ' .
            '[(ngModel)]="CONTENT_OTHERDATA.values[f.key]"></ion-input></ion-item>' .
            '</ng-container></ion-list>';
        if ($canedit) {
            $html .= '<div class="ion-padding"><ion-button expand="block" ' . self::btnstyle() .
                ' core-site-plugins-call-ws name="local_profileeditor_save_user" ' .
                '[params]="{userid: CONTENT_OTHERDATA.userid, values: (CONTENT_OTHERDATA.values | json)}" ' .
                '[preSets]="{getFromCache: false, saveToCache: false}" ' .
                'successMessage="' . self::str('mobile_savedmsg') . '" refreshOnSuccess="true">' .
                self::core('savechanges') . '</ion-button>' .
                '<p style="font-size:13px;opacity:.7">' . self::str('mobile_savenote') . '</p></div>';
        }

        return [
            'templates' => [['id' => 'main', 'html' => $html]],
            'otherdata' => [
                'brand' => self::brand(),
                'title' => fullname($user),
                'userid' => $user->id,
                'sub' => $user->username . ' · ' . $user->email,
                'fields' => json_encode($list),
                'values' => json_encode((object) $values),
            ],
        ];
    }

    /**
     * Bulk update: find users by a filter, then set one field for all of them.
     *
     * @param array $args
     * @return array
     */
    public static function mobile_bulk($args) {
        global $DB;
        $context = \context_system::instance();
        if (!has_capability('local/profileeditor:edit', $context)) {
            return self::nopermission();
        }
        $all = field_manager::get_ordered_fields();
        $filterfield = (string) ($args['filterfield'] ?? '');
        $filtervalue = trim((string) ($args['filtervalue'] ?? ''));
        $field = (string) ($args['field'] ?? '');
        $value = (string) ($args['value'] ?? '');
        $count = -1;
        if ($filterfield !== '' && $filtervalue !== '' && isset($all[$filterfield])) {
            [$where, $params] = user_query::build(['search' => '', 'filterfield' => $filterfield,
                'filtervalue' => $filtervalue, 'filterempty' => 0, 'showsuspended' => 0, 'sort' => '', 'dir' => 'ASC']);
            $count = $DB->count_records_sql("SELECT COUNT(1) FROM {user} u WHERE $where", $params);
        }
        $filters = [];
        $editable = [];
        foreach ($all as $k => $f) {
            $filters[] = ['key' => $k, 'name' => $f->name];
            if (field_manager::is_editable($f)) {
                $editable[] = ['key' => $k, 'name' => $f->name, 'type' => $f->datatype, 'options' => $f->options];
            }
        }

        $html = self::header('bulk') .
            '<ion-list>' .
            '<ion-item-divider><ion-label>1. ' . self::str('mobile_bulk_find') . '</ion-label></ion-item-divider>' .
            '<ion-item><ion-select label="' . self::str('filterby') . '" labelPlacement="stacked" interface="action-sheet" ' .
            '[(ngModel)]="CONTENT_OTHERDATA.filterfield">' .
            '<ion-select-option *ngFor="let f of CONTENT_OTHERDATA.filters" [value]="f.key">{{ f.name }}</ion-select-option>' .
            '</ion-select></ion-item>' .
            '<ion-item><ion-input label="' . self::str('contains') . '" labelPlacement="stacked" ' .
            '[(ngModel)]="CONTENT_OTHERDATA.filtervalue"></ion-input></ion-item>' .
            '</ion-list>' .
            '<div class="ion-padding-horizontal"><ion-button expand="block" fill="outline" ' .
            '(click)="updateContent({filterfield: CONTENT_OTHERDATA.filterfield, filtervalue: CONTENT_OTHERDATA.filtervalue, ' .
            'field: CONTENT_OTHERDATA.field, value: CONTENT_OTHERDATA.value})">' . self::str('mobile_bulk_count') .
            '</ion-button></div>' .
            '<ng-container *ngIf="CONTENT_OTHERDATA.count >= 0">' .
            '<p class="ion-padding-horizontal"><strong>{{ CONTENT_OTHERDATA.count }}</strong> ' .
            self::str('mobile_bulk_matching') . '</p>' .
            '<ion-list *ngIf="CONTENT_OTHERDATA.count > 0">' .
            '<ion-item-divider><ion-label>2. ' . self::str('mobile_bulk_set') . '</ion-label></ion-item-divider>' .
            '<ion-item><ion-select label="' . self::str('log_field') . '" labelPlacement="stacked" interface="action-sheet" ' .
            '[(ngModel)]="CONTENT_OTHERDATA.field">' .
            '<ion-select-option *ngFor="let f of CONTENT_OTHERDATA.editable" [value]="f.key">{{ f.name }}</ion-select-option>' .
            '</ion-select></ion-item>' .
            '<ng-container *ngFor="let f of CONTENT_OTHERDATA.editable"><ng-container *ngIf="f.key === CONTENT_OTHERDATA.field">' .
            '<ion-item *ngIf="f.type === \'menu\'"><ion-select label="' . self::str('mobile_newvalue') .
            '" labelPlacement="stacked" interface="action-sheet" [(ngModel)]="CONTENT_OTHERDATA.value">' .
            '<ion-select-option *ngFor="let o of f.options" [value]="o">{{ o }}</ion-select-option></ion-select></ion-item>' .
            '<ion-item *ngIf="f.type === \'checkbox\'"><ion-select label="' . self::str('mobile_newvalue') .
            '" labelPlacement="stacked" interface="popover" [(ngModel)]="CONTENT_OTHERDATA.value">' .
            '<ion-select-option value="1">' . self::core('yes') . '</ion-select-option>' .
            '<ion-select-option value="0">' . self::core('no') . '</ion-select-option></ion-select></ion-item>' .
            '<ion-item *ngIf="f.type === \'datetime\'"><ion-input type="date" label="' . self::str('mobile_newvalue') .
            '" labelPlacement="stacked" [(ngModel)]="CONTENT_OTHERDATA.value"></ion-input></ion-item>' .
            '<ion-item *ngIf="f.type !== \'menu\' && f.type !== \'checkbox\' && f.type !== \'datetime\'">' .
            '<ion-input label="' . self::str('mobile_newvalue') . '" labelPlacement="stacked" ' .
            '[(ngModel)]="CONTENT_OTHERDATA.value"></ion-input></ion-item>' .
            '</ng-container></ng-container>' .
            '</ion-list>' .
            '<div class="ion-padding" *ngIf="CONTENT_OTHERDATA.count > 0"><ion-button expand="block" ' . self::btnstyle() .
            ' [disabled]="!CONTENT_OTHERDATA.field" core-site-plugins-call-ws name="local_profileeditor_bulk_set" ' .
            '[params]="{field: CONTENT_OTHERDATA.field, value: CONTENT_OTHERDATA.value, ' .
            'filterfield: CONTENT_OTHERDATA.filterfield, filtervalue: CONTENT_OTHERDATA.filtervalue}" ' .
            '[preSets]="{getFromCache: false, saveToCache: false}" ' .
            'confirmMessage="' . self::str('mobile_bulk_confirm') . '" ' .
            'successMessage="' . self::str('mobile_bulk_done') . '" refreshOnSuccess="true">' .
            self::str('mobile_bulk_apply') . '</ion-button></div>' .
            '</ng-container>';

        return [
            'templates' => [['id' => 'main', 'html' => $html]],
            'otherdata' => [
                'brand' => self::brand(),
                'title' => get_string('mobile_nav_bulk', 'local_profileeditor'),
                'filters' => json_encode($filters),
                'editable' => json_encode($editable),
                'filterfield' => $filterfield,
                'filtervalue' => $filtervalue,
                'field' => $field,
                'value' => $value,
                'count' => $count,
            ],
        ];
    }

    /**
     * Recent changes.
     *
     * @param array $args
     * @return array
     */
    public static function mobile_log($args) {
        global $DB;
        $context = \context_system::instance();
        if (!has_capability('local/profileeditor:viewlog', $context)) {
            return self::nopermission();
        }
        $recs = $DB->get_records_sql("SELECT l.id, l.userid, l.fieldlabel, l.oldvalue, l.newvalue, l.source, l.timecreated,
                                             u.firstname, u.lastname, u.username,
                                             c.firstname AS cfirstname, c.lastname AS clastname
                                        FROM {local_profileeditor_log} l
                                   LEFT JOIN {user} u ON u.id = l.userid
                                   LEFT JOIN {user} c ON c.id = l.changedby
                                    ORDER BY l.timecreated DESC, l.id DESC", [], 0, 100);
        $rows = [];
        foreach ($recs as $r) {
            $rows[] = [
                'who' => trim($r->firstname . ' ' . $r->lastname) ?: (string) $r->username,
                'field' => preg_replace('/\s*\([^)]*\)$/', '', (string) $r->fieldlabel),
                'old' => (string) $r->oldvalue,
                'new' => (string) $r->newvalue,
                'by' => trim($r->cfirstname . ' ' . $r->clastname),
                'how' => get_string('log_src_' . ($r->source ?: 'editor'), 'local_profileeditor'),
                'time' => userdate($r->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
            ];
        }
        $html = self::header('log') .
            '<ion-list>' .
            '<ion-item *ngIf="!CONTENT_OTHERDATA.rows.length"><ion-label>' . self::str('log_empty') . '</ion-label></ion-item>' .
            '<ion-item *ngFor="let r of CONTENT_OTHERDATA.rows"><ion-label>' .
            '<p class="item-heading">{{ r.who }} · {{ r.field }}</p>' .
            '<p><span style="text-decoration:line-through;opacity:.6">{{ r.old || \'—\' }}</span> → ' .
            '<strong>{{ r.new || \'—\' }}</strong></p>' .
            '<p style="font-size:12px;opacity:.7">{{ r.time }} · {{ r.by }} · {{ r.how }}</p>' .
            '</ion-label></ion-item></ion-list>';
        return [
            'templates' => [['id' => 'main', 'html' => $html]],
            'otherdata' => ['brand' => self::brand(), 'title' => get_string('mobile_nav_log', 'local_profileeditor'),
                'rows' => json_encode($rows)],
        ];
    }
}
