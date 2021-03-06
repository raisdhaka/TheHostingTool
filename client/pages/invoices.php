<?php
/* Copyright © 2014 TheHostingTool
 *
 * This file is part of TheHostingTool.
 *
 * TheHostingTool is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TheHostingTool is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with TheHostingTool.  If not, see <http://www.gnu.org/licenses/>.
 */

// Check if called by script
if(THT != 1){die();}

class page {
    public function content(){ // Displays the page
        global $style, $db, $main, $invoice, $server;
        if($_GET['invoiceID']){
            require_once("../includes/paypal/paypal.class.php");
            $paypal = new paypal_class;
            if($paypal->validate_ipn()){
                $invoice->set_paid(mysql_real_escape_string($_GET['invoiceID']));
                $main->errors("Your invoice has been paid!");
                $client = $db->fetch_array($db->query("SELECT * FROM `<PRE>user_packs` WHERE `userid` = '{$_SESSION['cuser']}'"));
                if($client['status'] == '2') {
                    $server->unsuspend($client['id']);
                }
            }
            else {
                $main->errors("Your invoice hasn't been paid!");
            }
        }
        // List invoices. :)
        $query = $db->query("SELECT * FROM `<PRE>invoices` WHERE `uid` = '{$_SESSION['cuser']}' ORDER BY `id` ASC");
        $userdata = mysql_fetch_row($db->query("SELECT `user`,`firstname`,`lastname` FROM `<PRE>users` WHERE `id` = {$_SESSION['cuser']}"));
        $domain = mysql_fetch_row($db->query("SELECT `domain` FROM `<PRE>user_packs` WHERE `userid` = {$_SESSION['cuser']}"));
        $extra = array(
            "userinfo" 	=> "$userdata[2], $userdata[1] ($userdata[0])",
            "domain"	=> $domain[0]
        );
        $array2['list'] = "";
        while($array = $db->fetch_array($query)){
            $array['due'] = strftime("%D", $array['due']);
            $array["paid"] = ($array["is_paid"] == 1 ? "<span style='color:green;font-size:20px;'>Paid</span>" :
            "<span style='color:red;font-size:20px;'>Unpaid</span>");
            $array["pay"] = ($array["is_paid"] == 0 ?
            '<input type="button" name="pay" id="pay" value="Pay Now" onclick="doswirl(\''.$array['id'].'\')" />' :
            '');
            $array['amount'] = $array['amount']." ".$db->config("currency");
            $array2['list'] .= $style->replaceVar("tpl/invoices/invoice-list-item.tpl", array_merge($array, $extra));
        }
        $array2['num'] = mysql_num_rows($query);
        echo $style->replaceVar("tpl/invoices/client-page.tpl", $array2);
    }
}
