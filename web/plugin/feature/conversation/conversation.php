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
                    tblIn.in_sender AS sender,
                    '' AS message_out,
                    tblIn.in_message AS message_in
                FROM
                    playsms_tblSMSIncoming AS tblIn
                WHERE
                    tblIn.flag_deleted = 0
                    AND tblIn.in_uid = " . $user_config['uid'] . "
             ) ORDER BY sender, datetime";

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
$k = 0;
$list_sender = array();
$flag_section = false;
for ($j = 0; $j < count($list); $j++) {
    $list[$j] = core_display_data($list[$j]);
    $id = $list[$j]['id'];
    $sender = $list[$j]['sender'];
    $p_desc = phonebook_number2name($sender);
    $current_sender = $sender;
    if ($p_desc) {
        $current_sender = "$sender<br />$p_desc";
    }
    $datetime = core_display_datetime($list[$j]['datetime']);
    $msg_in = core_display_text($list[$j]['message_in']);
    $msg_out = core_display_text($list[$j]['message_out']);
    $msg = $msg_in.$msg_out;
    $reply = '';
    $forward = '';
    if ( $msg && $sender) {
        $reply = _a('index.php?app=main&inc=core_sendsms&op=sendsms&do=reply&message=' . urlencode($msg) . '&to=' . urlencode($sender) , $icon_config['reply']);
        $forward = _a('index.php?app=main&inc=core_sendsms&op=sendsms&do=forward&message=' . urlencode($msg) , $icon_config['forward']);
    }
    $i--;
    $datetime_in = $satus_in = $reply_in = $forward_in = '';
    $datetime_out = $satus_out = $reply_out = $forward_out = '';
    if ( $msg_in != '' ) {
        $datetime_in = $datetime;
        $satus_in = $status;
        $reply_in = $reply;
        $forward_in = $forward;
    } else {
        if ( $msg_out != '' ) {
            $datetime_out = $datetime;
            $satus_out = $status;
            $reply_out = $reply;
            $forward_out = $forward;
        }
    }
    if (!in_array($sender, $list_sender)) {
        $list_sender[] = $sender;
        $table_class = '';
    } else {
        $table_class = $sender;
    }

    $data = array(
        'tr_class' => $tr_class,
        'table_class' => $table_class,
        'current_sender' => $current_sender,
        'msg_in' => $msg_in,
        'msg_out' => $msg_out,
        'datetime_in' => $datetime_in,
        'datetime_out' => $datetime_out,
        'status_in' => $status_in,
        'status_out' => $status_out,
        'reply_in' => $reply_in,
        'reply_out' => $reply_out,
        'forward_in' => $forward_in,
        'forward_out' => $forward_out,
        'id' => $id,
        'j' => $j
    );

    $tpl['loops']['data'][] = $data;
}


$error_content = '';
if ($err = $_SESSION['error_string']) {
    $error_content = "<div class=error_string>$err</div>";
}
$tpl['vars']['ERROR'] = $error_content;
$tpl['name'] = 'conversation';
$content = tpl_apply($tpl);
_p($content);
