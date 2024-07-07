<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Helper as H;

/**
 * Класс-сущность  документ возвратная накладная
 *
 */
class ReturnIssue extends Document
{
    public function generateReport() {


        $i = 1;
        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $item) {

            if (isset($detail[$item->item_id])) {
                $detail[$item->item_id]['quantity'] += $item->quantity;
            } else {
                $name = $item->itemname;
                if (strlen($item->snumber) > 0) {
                    $name .= ' (' . $item->snumber . ',' . H::fd($item->sdate) . ')';
                }

                $detail[] = array("no"         => $i++,
                                  "tovar_name" => $name,
                                  "quantity"   => H::fqty($item->quantity),
                                  "price"      => H::fa($item->price),
                                  "msr"        => $item->msr,
                                  "amount"     => H::fa($item->quantity * $item->price)
                );
            }
        }


        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "firm_name"       => $this->headerdata["firm_name"],
                        "customer_name"   => $this->customer_name,
                        "notes"           => nl2br($this->notes),
                        "document_number" => $this->document_number,
                        "fiscalnumber"  => strlen($this->headerdata["fiscalnumber"]) > 0 ? $this->headerdata["fiscalnumber"] : false,
                        "total"           => H::fa($this->amount),
                        "payamount"           => H::fa($this->payamount),
                        "payed"           => H::fa($this->payed)
        );

        $report = new \App\Report('doc/returnissue.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();

        foreach ($this->unpackDetails('detaildata') as $item) {

            $lp = $item->getLastPartion($this->headerdata['store'], $item->snumber, false);

            $stock = \App\Entity\Stock::getStock($this->headerdata['store'], $item->item_id, $lp, $item->snumber, $item->sdate, true);

            $sc = new Entry($this->document_id, $item->amount, $item->quantity);
            $sc->setStock($stock->stock_id);

            //  $sc->setExtCode(($item->price - $stock->partion)); //Для АВС
            //  $sc->setCustomer($this->customer_id);
            $sc->setOutPrice($item->price);
            $sc->tag=Entry::TAG_RSELL;
            $sc->save();
        }

        $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, 0 - $this->payed, $this->headerdata['payment']);
        if ($payed > 0) {
            $this->payed = $payed;
        }
        \App\Entity\IOState::addIOState($this->document_id, 0 - $this->payed, \App\Entity\IOState::TYPE_BASE_INCOME);
      $this->DoBalans() ;

        if($this->headerdata["bonus"] > 0) {
                $pay = new \App\Entity\CustAcc();

                $pay->document_id = $this->document_id;
                $pay->amount = $this->headerdata["bonus"];
                $pay->optype = \App\Entity\CustAcc::BONUS;
               
                $pay->customer_id = $this->customer_id;

                $pay->save();       
        }

        //штраф  сотруднику
       if ($this->parent_id > 0) {
            $parent = Document::load($this->parent_id);
            $user = \App\Entity\User::load($parent->user_id);        
            $disc = \App\System::getOptions("discount");
            $emp_id = \App\System::getUser()->employee_id ;
            if($emp_id >0 && $disc["fineret"] >0  && $parent->meta_name=='POSCheck') {
                $b =  $this->amount * $disc["fineret"] / 100;
                $ua = new \App\Entity\EmpAcc();
                $ua->optype = \App\Entity\EmpAcc::FINE;
                $ua->document_id = $this->document_id;
                $ua->emp_id = $emp_id;
                $ua->amount = 0-$b;
                $ua->save();

            }
            
        }     
        
        return true;
    }

    protected function getNumberTemplate() {
        return 'BK-000000';
    }
    public function DoBalans() {
          $conn = \ZDB\DB::getConnect();
          $conn->Execute("delete from custacc where customer_id =" . $this->customer_id);

              
        //платежи       
        foreach($conn->Execute("select abs(amount) as amount ,paydate from paylist  where  coalesce(amount,0) <> 0 and document_id = {$this->document_id}  ") as $p){
            $b = new \App\Entity\CustAcc();
            $b->customer_id = $this->customer_id;
            $b->document_id = $this->document_id;
            $b->amount = 0-$p['amount'];
            $b->createdon = strtotime($p['paydate']);
            $b->optype = \App\Entity\CustAcc::SELLER;
            $b->save();
        }
        
        if($this->payamount >0) {
            $b = new \App\Entity\CustAcc();
            $b->customer_id = $this->customer_id;
            $b->document_id = $this->document_id;
            $b->amount = $this->payamount;
            $b->optype = \App\Entity\CustAcc::BUYER;
            $b->save();
        }
    }
}
