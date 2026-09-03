<?php
namespace EvePrices\Sources;

class Redis
{

    private $redis;

    public function __construct($host, $port, $scheme)
    {
        $this->redis = new \Predis\Client(array('scheme' => $scheme,'host'   => $host,'port'   => $port));
    }

    private function extractPrice($rawvalue)
    {
        // New aggregate format: weightedaverage|maxval|minval|stddev|median|volume|numorders|fivepercent
        $values=explode("|", $rawvalue);
        $price=isset($values[7]) ? $values[7] : null;
        if (!(is_numeric($price))) {
            $price=0;
        }
        return $price;
    }

    public function returnPrice($typeid, $regionid)
    {
        $pricedatasell=$this->redis->get($regionid.'|'.$typeid.'|false');
        $pricedatabuy=$this->redis->get($regionid.'|'.$typeid.'|true');
        $price=$this->extractPrice($pricedatasell);
        $pricebuy=$this->extractPrice($pricedatabuy);
        $eveprices=$this->redis->get('eveprice-'.$typeid);
        $values=explode("|", $eveprices);
        $average=$values[0];
        $adjusted=$values[1];
        if (!(is_numeric($adjusted))) {
            $adjusted=0;
        }
        if (!(is_numeric($average))) {
            $average=0;
        }
        return array('sell'=>$price,'buy'=>$pricebuy,'adjusted'=>$adjusted,'average'=>$average);
    }


    public function returnPriceArray($typeids, $regionid)
    {
        $priceArray=array();
        foreach ($typeids as $typeid) {
            $pricedatasell=$this->redis->get($regionid.'|'.$typeid.'|false');
            $pricedatabuy=$this->redis->get($regionid.'|'.$typeid.'|true');
            $price=$this->extractPrice($pricedatasell);
            $pricebuy=$this->extractPrice($pricedatabuy);
            $eveprices=$this->redis->get('eveprice-'.$typeid);
            if (isset($eveprices)) {
                $values=explode("|", $eveprices);
                $average=$values[0];
                $adjusted=$values[1];
                if (!(is_numeric($adjusted))) {
                    $adjusted=0;
                }
                if (!(is_numeric($average))) {
                    $average=0;
                }
            } else {
                $adjusted=0;
                $average=0;
            }
            $priceArray[$typeid]=array('sell'=>$price,'buy'=>$pricebuy,'adjusted'=>$adjusted,'average'=>$average);
        }
        return $priceArray;
    }

    public function populateArray($inputarray, $regionid)
    {
        $populatedArray=array();
        foreach ($inputarray as $entry) {
            $typeid=$entry['typeid'];
            $pricedatasell=$this->redis->get($regionid.'|'.$typeid.'|false');
            $pricedatabuy=$this->redis->get($regionid.'|'.$typeid.'|true');
            $price=$this->extractPrice($pricedatasell);
            $pricebuy=$this->extractPrice($pricedatabuy);
            $eveprices=$this->redis->get('eveprice-'.$typeid);
            $values=explode("|", $eveprices);
            $average=$values[0];
            $adjusted=$values[1];
            if (!(is_numeric($adjusted))) {
                $adjusted=0;
            }
            if (!(is_numeric($average))) {
                $average=0;
            }
            $entry['price']=array($price,$pricebuy,$adjusted,$average);
            $populatedArray[]=$entry;
        }
        return $populatedArray;
    }
}
