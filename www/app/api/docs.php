<?php


namespace App\API;

use \App\Entity\Doc\Document;
use \App\Entity\Item;
use \App\Helper as H;

class docs extends \App\API\Base\JsonRPC
{
    //список  статусов
    public function statuslist() {
        $list = \App\Entity\Doc\Document::getStateList();

        return $list;
    }

    //список  филиалов
    public function branchlist() {
        $list = \App\Entity\Branch::findArray('branch_name', '', 'branch_name');

        return $list;
    }


    //записать ордер
    public function createorder($args) {
        $options = \App\System::getOptions('common');

        if (strlen($args['number']) == 0) {
            throw  new  \Exception(H::l("apinumber"));  //не задан  номер

        }


        $num1 = Document::qstr("%<apinumber>{$args['number']}</apinumber>%");
        $num2 = Document::qstr("%<apinumber><![CDATA[{$args['number']}]]></apinumber>%");
        $doc = Document::getFirst("  content   like  {$num1} or  content   like  {$num2}  ");
        if ($doc != null) {
            throw  new  \Exception(H::l("apinumberexists", $args['number']));   //номер уже  существует
        }

        if ($args['customer_id'] > 0) {
            $c = \App\Entity\Customer::load($args['customer_id']);
            if ($c == null) {
                throw  new  \Exception(H::l("apicustnotfound"));
            } else {
                $doc->customer_id = $args['customer_id'];
            }
        }
        $doc = Document::create('Order');

        if ($options['usebranch'] == 1) {
            if ($args['branch_id'] > 0) {
                $doc->branch_id = $args['branch_id'];
            } else {
                throw  new  \Exception(H::l("apinobranch"));
            }
        }

        $doc->document_number = $doc->nextNumber();
        $doc->document_date = time();
        $doc->state = Document::STATE_NEW;
        $doc->headerdata["apinumber"] = $args['number'];
        $doc->headerdata["phone"] = $args['phone'];
        $doc->headerdata["email"] = $args['email'];
        $doc->headerdata["ship_address"] = $args['ship_address'];

        $doc->notes = @base64_decode($args['description']);
        $details = array();
        $total = 0;
        if (is_array($args['items']) && count($args['items']) > 0) {
            foreach ($args['items'] as $it) {
                if (strlen($it['item_code']) == 0) {
                    throw  new \Exception(H::l("apientercode"));
                }
                $item = Item::getFirst("disabled<> 1 and item_code=" . Item::qstr($it['item_code']));

                if ($item instanceof Item) {

                    $item->quantity = $it['quantity'];
                    $item->price = $it['price'];
                    $item->amount = $item->quantity * $item->price;
                    $total = $total + $item->quantity * $item->price;
                    $details[$item->item_id] = $item;
                } else {
                    throw  new  \Exception(H::l("apiitemnotfound", $it['code']));
                }
            }
        } else {
            throw  new  \Exception(H::l("apinoitems"));
        }
        if (count($details) == 0) {
            throw  new  \Exception(H::l("apinoitems"));
        }
        $doc->packDetails('detaildata', $details);
        if ($args['total'] > 0) {
            $doc->amount = $args['total'];
        } else {
            $doc->amount = $total;
        }

        $doc->payamount = $doc->amount;

        $doc->save();

        return $doc->document_number;
    }

    //записать ТТН
    public function createttn($args) {

        if (strlen($args['number']) == 0) {
            throw  new  \Exception(H::l("apinumber"));  //не задан  номер

        }
        $num1 = Document::qstr("%<apinumber>{$args['number']}</apinumber>%");
        $num2 = Document::qstr("%<apinumber><![CDATA[{$args['number']}]]></apinumber>%");
        $doc = Document::getFirst("  content   like  {$num1} or  content   like  {$num2}  ");
        if ($doc != null) {
            throw  new  \Exception(H::l("apinumberexists", $args['number']));   //номер уже  существует
        }

        if ($args['customer_id'] > 0) {
            $c = \App\Entity\Customer::load($args['customer_id']);
            if ($c == null) {
                throw  new  \Exception(H::l("apicustnotfound"));
            } else {
                $doc->customer_id = $args['customer_id'];
            }
        }
        $doc = Document::create('TTN');
        $doc->document_number = $doc->nextNumber();
        $doc->document_date = time();
        $doc->state = Document::STATE_NEW;
        $doc->headerdata["apinumber"] = $args['number'];
        $doc->headerdata["phone"] = $args['phone'];
        $doc->headerdata["email"] = $args['email'];
        $doc->headerdata["ship_address"] = $args['ship_address'];

        $doc->notes = @base64_decode($args['description']);
        $details = array();
        $total = 0;
        if (is_array($args['items']) && count($args['items']) > 0) {
            foreach ($args['items'] as $it) {
                if (strlen($it['item_code']) == 0) {
                    throw  new \Exception(H::l("apientercode"));
                }
                $item = Item::getFirst("disabled<> 1 and item_code=" . Item::qstr($it['item_code']));

                if ($item instanceof Item) {

                    $item->quantity = $it['quantity'];
                    $item->price = $it['price'];
                    $item->amount = $item->quantity * $item->price;
                    $total = $total + $item->quantity * $item->price;
                    $details[$item->item_id] = $item;
                } else {
                    throw  new  \Exception(H::l("apiitemnotfound", $it['code']));
                }
            }
        } else {
            throw  new  \Exception(H::l("apinoitems"));
        }
        if (count($details) == 0) {
            throw  new  \Exception(H::l("apinoitems"));
        }
        $doc->packDetails('detaildata', $details);
        if ($args['total'] > 0) {
            $doc->amount = $args['total'];
        } else {
            $doc->amount = $total;
        }

        $doc->payamount = $doc->amount;

        $doc->save();

        return $doc->document_number;
    }

    // проверка  статусов документов по  списку  номеров
    public function checkstatus($args) {
        $list = array();

        if (!is_array($args['numbers'])) {
            throw new \Exception(H::l("apiinvalidparameters"));
        }
        foreach ($args['numbers'] as $num) {
            $num1 = Document::qstr("%<apinumber>{$num}</apinumber>%");
            $num2 = Document::qstr("%<apinumber><![CDATA[{$num}]]></apinumber>%");
            $doc = Document::getFirst("  content   like  {$num1} or content   like  {$num2}  ");
            if ($doc instanceof Document) {
                $list[] = array(
                    "number"     => $num,
                    "status"     => $doc->state,
                    "statusname" => Document::getStateName($doc->state)
                );
            }
        }

        return $list;
    }

    //запрос на  отмену
    public function cancel($args) {
        $doc = null;
        if (strlen($args['number']) > 0) {
            $num1 = Document::qstr("%<apinumber>{$args['number']}</apinumber>%");
            $num2 = Document::qstr("%<apinumber><![CDATA[{$args['number']}]]></apinumber>%");

            $doc = Document::getFirst(" content like {$num1}  or content like {$num2} ");

        }
        if ($doc == null) {
            throw new  \Exception(H::l("apinodoc"));
        }

        $user = \App\System::getUser();
        $admin = \App\Entity\User::getByLogin('admin');
        $n = new \App\Entity\Notify();
        $n->user_id = $admin->user_id;
        $n->dateshow = time();
        $n->message = H::l("apiasccancel", $user->username, $doc->document_number, $args['reason']);
        $n->save();

    }


}