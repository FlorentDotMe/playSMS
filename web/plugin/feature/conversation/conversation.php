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

switch (_OP_) {
    case 'conversation':
        $conditions_out = array(
            'uid' => $user_config['uid'],
            'flag_deleted' => 0
        );
        $conditions_in = array(
            'uid' => $user_config['in_uid'],
            'flag_deleted' => 0

        );
        $count = dba_count(_DB_PREF_ . '_tblSMSOutgoing', $conditions_out);
        $count += dba_count(_DB_PREF_ . '_tblSMSIncoming', $conditions_in);
        if ( $count == 0 ) {
            $count = 1;
        }
        $base_url = "index.php?app=main&inc=feature_conversation&route=conversation&op=conversation";
        $nav = themes_nav($count,$base_url);

        $db_query = "(
                        SELECT
                            tblOut.p_dst AS sender
                        FROM
                            playsms_tblSMSOutgoing AS tblOut
                        WHERE
                            tblOut.flag_deleted = 0
                            AND tblOut.uid = " . $user_config['uid'] . "
                     ) UNION (
                        SELECT
                            tblIn.in_sender AS sender
                        FROM
                            playsms_tblSMSIncoming AS tblIn
                        WHERE
                            tblIn.flag_deleted = 0
                            AND tblIn.in_uid = " . $user_config['uid'] . "
                     ) ORDER BY sender ASC";

        $q = 0;
        $list_sender = '';
        $db_result = @dba_query($db_query);
        while ($db_row = dba_fetch_array($db_result)) {
            if($list_sender != '') {
                $list_sender = $list_sender . ',';
            }
            $list_sender = $list_sender . '{id:' . $db_row['sender'] . ',text:"' . $db_row['sender'] . '"}';
            $q++;
        }

        // MySQL Query
        // Expected output
        //+----+----------+--------+-------------+------------+
        //| id | datetime | sender | message_out | message_in |
        //+----+----------+--------+-------------+------------+

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
                     ) ORDER BY datetime DESC
                       LIMIT " . $nav['limit'] . "
                       OFFSET " . $nav['offset'];

        $db_result = @dba_query($db_query);
        while ($db_row = dba_fetch_array($db_result)) {
            $list[] = $db_row;
        }

        unset($tpl);
        $tpl = array(
            'vars' => array(
                // Read feature
                'SEARCH_FORM' => $search['form'],
                'NAV_FORM' => $nav['form'],
                'Conversations' => _('Conversations') ,
                'Export' => $icon_config['export'],
                'Delete' => $icon_config['delete'],
                'New' => $icon_config['new'],
                'Message in' => _('Received'),
                'Message out' => _('Sent'),
                'Last update' => _('Last update'),
                'ARE_YOU_SURE' => _('Are you sure you want to delete these items ?'),
                // Send SMS feature
                'Send message' => _('Send message'),
                'Send to' => _('Send to'),
                'Message' => _('Message'),
                'Unicode message' => _('Unicode message'),
                'Send' => _('Send'),
                'to' => $to,
                'chars' => _('chars'),
                'SMS' => _('SMS'),
                'DataSelect' => $list_sender,
                'DIALOG_DISPLAY' => _dialog(),
                'HTTP_PATH_BASE' => _HTTP_PATH_BASE_,
                'HTTP_PATH_THEMES' => _HTTP_PATH_THEMES_,
                'MAX_SMS_LENGTH' => $core_config['main']['max_sms_length'] - strlen($user_config['footer']),
                'MAX_SMS_LENGTH_UNICODE' =>$core_config['main']['max_sms_length_unicode'] - strlen($user_config['footer'])
            )
        );

        $i = $nav['top'];
        $j = 0;
        $flag_section = false;
        for ($j = 0; $j < count($list); $j++) {
            $list[$j] = core_display_data($list[$j]);
            $id = $list[$j]['id'];
            $sender = $list[$j]['sender'];
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
                $db = 'in';
                // Not required
                //$status_in = $status;
            } else {
                if ( $msg_out != '' ) {
                    $datetime_out = $datetime;
                    $status_out = $status;
                    $db = 'out';
                }
            }

            // Data queue for template
            $data[$sender][] = array(
                'header' => $header,
                'tr_attr' => $tr_attr,
                'sender' => $sender,
                'msg_in' => $msg_in,
                'msg_out' => $msg_out,
                'datetime_in' => $datetime_in,
                'datetime_out' => $datetime_out,
                'status_in' => $status_in,
                'status_out' => $status_out,
                'id' => $id,
                'db' => $db,
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
                    $reply = '<a href="#top" onclick="replyto(event,\'' . $cell['sender'] . '\');">' . $icon_config['reply'] . '</a>';
                    // Format conversation header
                    $header = '
                        <tr data-toggle="collapse" data-target=".collapse' . $l . '" class="accordion-toggle text-center warning">
                            <td colspan="4">
                                <b>' . $cell['sender'] . '</b> ' .
                                $reply . ' (' . _('Last update') . ': ' . $cell['datetime_in'] . $cell['datetime_out'] . ')
                                </td>
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
        $tpl['name'] = 'conversation';
        _p(tpl_apply($tpl));
        break;

    case 'actions':
        $nav = themes_nav_session();
        $go = $_REQUEST['go'];
        switch ($go) {
            case 'delete':
                for ($i = 0; $i < $nav['limit']; $i++) {
                    $checkid = $_POST['checkid' . $i];
                    // Syntax: [in|out].[ID]
                    $item = explode(".",$_POST['itemid' . $i]);
                    $itemdb = $item[0];
                    $itemid = $item[1];
                    if (($checkid == "on") && $itemid) {
                        $up = array(
                            'c_timestamp' => mktime() ,
                            'flag_deleted' => '1'
                        );
                        switch ($itemdb) {
                            case 'in':
                                dba_update(_DB_PREF_ . '_tblSMSIncoming', $up, array(
                                    'in_uid' => $user_config['uid'],
                                    'in_id' => $itemid
                                ));
                                break;
                            case 'out':
                                dba_update(_DB_PREF_ . '_tblSMSOutgoing', $up, array(
                                    'uid' => $user_config['uid'],
                                    'id' => $itemid
                                ));
                                break;
                        }
                    }
                }
                $_SESSION['dialog']['info'][] = _('Selected message has been deleted');
                break;

            case 'sendsms':
                // sender ID
                if ($core_config['main']['allow_custom_sender']) {
                    $sms_sender = trim($_REQUEST['sms_sender']);
                } else {
                    $sms_sender = sendsms_get_sender($user_config['username']);
                }
                
                // SMS footer
                $nofooter = false;
                $sms_footer = $user_config['footer'];
                
                // unicode or not
                $msg_unicode = $_REQUEST['msg_unicode'];
                $unicode = "0";
                if ($msg_unicode == "on") {
                    $unicode = "1";
                }
                
                // SMS message
                $message = $_REQUEST['message'];
                
                // destination numbers
                if ($sms_to = trim($_REQUEST['p_num_text'])) {
                    $sms_to = explode(',', $sms_to);
                }
                
                if ($sms_to[0] && $message) {

                    list($ok, $to, $smslog_id, $queue, $counts, $sms_count, $sms_failed, $error_strings) = sendsms_helper($user_config['username'], $sms_to, $message, $sms_type, $unicode, '', $nofooter, $sms_footer, $sms_sender, $sms_schedule, $reference_id);

                    if (!$sms_count && $sms_failed) {
                        $_SESSION['dialog']['danger'][] = _('Fail to send message to all destinations') . " (" . _('queued') . ":" . (int) $sms_count . " " . _('failed') . ":" . (int) $sms_failed . ")";
                    } else if ($sms_count && $sms_failed) {
                        $_SESSION['dialog']['danger'][] = _('Your message has been delivered to some of the destinations') . " (" . _('queued') . ":" . (int) $sms_count . " " . _('failed') . ":" . (int) $sms_failed . ")";
                    } else if ($sms_count && !$sms_failed) {
                        $_SESSION['dialog']['info'][] = _('Your message has been delivered to queue') . " (" . _('queued') . ":" . (int) $sms_count . " " . _('failed') . ":" . (int) $sms_failed . ")";
                    } else {
                        if (!is_array($error_strings)) {
                            $_SESSION['dialog']['danger'][] = $error_strings;
                        } else {
                            $_SESSION['dialog']['danger'][] = _('System error has occured');
                        }
                    }
                } else {
                    $_SESSION['dialog']['danger'][] = _('You must select receiver and your message should not be empty');
                }
        }
        $ref = $nav['url'] . '&page=' . $nav['page'] . '&nav=' . $nav['nav'];
        header("Location: " . _u($ref));
        exit();
        break;
    
}
