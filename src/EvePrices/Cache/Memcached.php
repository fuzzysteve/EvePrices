<?php
namespace EvePrices\Cache;

class Memcached implements \EvePrices\CacheInterface
{

    private $memcache;

    public function __construct($host, $port)
    {
        $this->memcache = new \Memcache;
        $this->memcache->connect($host, $port);
    }

    public function checkPrice($typeid, $regionid)
    {
        $pricedatasell=$this->memcache->get('cache'.$regionid.'sell-'.$typeid);
        $pricedatabuy=$this->memcache->get('cache'.$regionid.'buy-'.$typeid);
        if (!(is_numeric($pricedatasell))) {
            return null;
        }
        if (!(is_numeric($pricedatabuy))) {
            return null;
        }
        return array((float)$pricedatasell,(float)$pricedatabuy);
    }

    public function checkPriceArray($typeids, $regionid)
    {
        $priceArray=array();
        $toget=array();
        foreach ($typeids as $typeid) {
            $pricedatasell=$this->memcache->get('cache'.$regionid.'sell-'.$typeid);
            $pricedatabuy=$this->memcache->get('cache'.$regionid.'buy-'.$typeid);
            if (!(is_numeric($pricedatasell))) {
                $toget[]=$typeid;
                continue;
            }
            if (!(is_numeric($pricedatabuy))) {
                $toget[]=$typeid;
                continue;
            }
            $priceArray[$typeid]=array((float)$pricedatasell,(float)$pricedatabuy);
        }
        return array($priceArray,$toget);
    }

    public function checkPopulatedArray($inputarray, $regionid)
    {
        $populatedArray=array();
        $toget=array();
        foreach ($inputarray as $entry) {
            $typeid=$entry['typeid'];
            $pricedatasell=$this->memcache->get('cache'.$regionid.'sell-'.$typeid);
            $pricedatabuy=$this->memcache->get('cache'.$regionid.'buy-'.$typeid);
            if (!(is_numeric($pricedatasell))) {
                $toget[]=$entry;
                continue;
            }
            if (!(is_numeric($pricedatabuy))) {
                $toget[]=$entry;
                continue;
            }
                $entry['price']=array((float)$pricedatasell,(float)$pricedatabuy);
                $populatedArray[]=$entry;
        }
        return array($populatedArray,$toget);
    }


    public function setPrice($typeid, $regionid, $pricearray)
    {
        $this->memcache->set('cache'.$regionid.'sell-'.$typeid, $pricearray[0], 3600);
        $this->memcache->set('cache'.$regionid.'buy-'.$typeid, $pricearray[1], 3600);
    }

    public function setPriceArray($typeids, $regionid)
    {
        foreach ($typeids as $typeid => $pricearray) {
            $this->memcache->set('cache'.$regionid.'sell-'.$typeid, $pricearray[0], 3600);
            $this->memcache->set('cache'.$regionid.'buy-'.$typeid, $pricearray[1], 3600);
        }
    }

    public function setPopulatedArray($inputarray, $regionid)
    {
        foreach ($inputarray as $entry) {
            $this->memcache->set('cache'.$regionid.'sell-'.$entry['typeid'], $entry['price'][0], 3600);
            $this->memcache->set('cache'.$regionid.'buy-'.$entry['typeid'], $entry['price'][1], 3600);
        }
    }
}
