<?php

/**
 * This file is part of playSMS.
 *
 * playSMS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * playSMS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with playSMS.  If not, see <http://www.gnu.org/licenses/>.
 */

defined('_SECURE_') or die('Forbidden');

if (!auth_isvalid()) {
	auth_block();
};

$base_url = 'index.php?app=main&inc=feature_conversation&route=conversation&op=conversation';

#################
# MySQL Query
# Expected output
#+----+----------+--------+-------------+------------+
#| id | datetime | sender | message_out | message_in |
#+----+----------+--------+-------------+------------+

$db_query = "(  
                SELECT 
                    tblOut.id AS id,
                    tblOut.p_datetime AS datetime,
                    tblOut.p_status AS status,
                    tblOut.p_dst AS sender,
                    tblOut.p_msg AS message_out,
                    '' AS message_in
                FROM
                    playsms_tblSMSOutgoing AS tblOut
                WHERE
                    tblOut.flag_deleted = 0
                    AND tblOut.uid = " . $user_config['uid'] . "
             ) UNION (
                SELECT
                    tblIn.in_id AS id,
                    tblIn.in_datetime AS datetime,
                    tblIn.in_status AS status,
                    tblIn.in_sender AS sender,
                    '' AS message_out,
                    tblIn.in_message AS message_in
                FROM
                    playsms_tblSMSIncoming AS tblIn
                WHERE
                    tblIn.flag_deleted = 0
                    AND tblIn.in_uid = " . $user_config['uid'] . "
             ) ORDER BY datetime DESC";

$db_result = @dba_query($db_query);
while ($db_row = dba_fetch_array($db_result)) {
    $list[] = $db_row;
}
unset($tpl);
$tpl = array(
    'vars' => array(
        'SEARCH_FORM' => $search['form'],
        'NAV_FORM' => $nav['form'],
        'Conversations' => _('Conversations') ,
        'Export' => $icon_config['export'],
        'Delete' => $icon_config['delete'],
        'Me' => _('Me'),
        'Recipient' => _('Recipient'),
        'Message in' => _('Message in'),
        'Message out' => _('Message out'),
        'ARE_YOU_SURE' => _('Are you sure you want to delete these items ?')
    )
);

$i = $nav['top'];
$j = 0;
$list_sender = array();
$flag_section = false;
for ($j = 0; $j < count($list); $j++) {
    $list[$j] = core_display_data($list[$j]);
    $id = $list[$j]['id'];
    $sender = $list[$j]['sender'];
    $desc = phonebook_number2name($sender);
    $current_sender = $sender;
    if ($desc) {
        $current_sender = "$sender<br />$desc";
    }
    $datetime = core_display_datetime($list[$j]['datetime']);
    $msg_in = core_display_text($list[$j]['message_in']);
    $msg_out = core_display_text($list[$j]['message_out']);
    $status = $list[$j]['status'];

    // 0 = pending
    // 1 = sent
    // 2 = failed
    // 3 = delivered
    if ($status == "1") {
        $status = "<span class=status_sent />";
    } else if ($status == "2") {
        $status = "<span class=status_failed />";
    } else if ($status == "3") {
        $status = "<span class=status_delivered />";
    } else {
        $status = "<span class=status_pending />";
    }
    $status = strtolower($status);

    $i--;
    $datetime_in = $status_in = '';
    $datetime_out = $status_out = '';
    if ( $msg_in != '' ) {
        $datetime_in = $datetime;
        $status_in = $status;
    } else {
        if ( $msg_out != '' ) {
            $datetime_out = $datetime;
            $status_out = $status;
        }
    }

    // Data queue for template
    $data[$sender][] = array(
        'header' => $header,
        'tr_attr' => $tr_attr,
        'current_sender' => $current_sender,
        'sender' => $sender,
        'msg_in' => $msg_in,
        'msg_out' => $msg_out,
        'datetime_in' => $datetime_in,
        'datetime_out' => $datetime_out,
        'status_in' => $status_in,
        'status_out' => $status_out,
        'id' => $id,
        'j' => $j
    );
}

// Message counter
$m = 0;
// Conversation counter
$l = 0;

foreach ( $data as $sdata ) {
    // Reset message counter
    $m = 0;
    // Increase conversation counter
    $l++;

    foreach ( $sdata as $cell ) {
        // Insert the collapse control header for each conversation
        if ($m == 0) {
            $m++;
            // Format reply button
            $reply = _a('index.php?app=main&inc=core_sendsms&op=sendsms&do=reply&to=' . urlencode($cell['sender']) , $icon_config['reply']);
            // Format conversation header
            $header = '
                <tr data-toggle="collapse" data-target=".collapse' . $l . '" class="accordion-toggle text-center warning">
                    <td colspan="4">' . $cell['current_sender'] . ' ' . $reply . '</td>
                </tr>';
        } else {
            // Empty conversation header if not the first message
            $header = '';
        }
        // Set attributes for messages of each conversation
        $tr_attr = 'class="collapse' . $l . ' collapse accordion-body"';
        // Update dynamic fields
        $cell['header'] = $header;
        $cell['tr_attr'] = $tr_attr;
        // Push in the queue
        $tpl['loops']['data'][] = $cell;
    }
}

// Manage errors
$error_content = '';
if ($err = $_SESSION['error_string']) {
    $error_content = "<div class=error_string>$err</div>";
}
$tpl['vars']['ERROR'] = $error_content;
$tpl['name'] = 'conversation';
$content = tpl_apply($tpl);
_p($content);
