<?php
namespace EvePrices;

class EvePrices
{

    const VERSION="1.0.0";

    private $region;
    private $priceSource;
    private $cache;

    public function __construct($args = array('source'=>'redis','region'=>10000002,'host'=>'127.0.0.1','port'=>6379,'scheme'=>'tcp'))
    {
        switch ($args['source']) {
            case 'redis':
                $this->priceSource=new \EvePrices\Sources\Redis($args['host'], $args['port'], $args['scheme']);
                break;
            case 'populatedmemcached':
                $this->priceSource=new \EvePrices\Sources\Memcached($args['host'], $args['port']);
                break;
            default:
                throw new \Exception("EvePrices doesn't understand source type ".$args['source']);
        }
        if (isset($args['cache'])) {
            switch ($args['cache']) {
                case 'memcache':
                    $this->cache=new \EvePrices\Cache\Memcached($args['cachehost'], $args['cacheport']);
                    break;
                default:
                    throw new \Exception("EvePrices doesn't understand cache type ".$args['cache']);
            }
        }
        if (isset($args['region']) and is_numeric($args['region'])) {
            $this->region=$args['region'];
        } else {
            $this->region=10000002;
        }
        if ($this->region==10000002) {
            $this->region='forge';
        }
    }

    public function setRegion($regionid)
    {
        if (isset($regionid) and is_numeric($regionid)) {
            $this->region=$regionid;
        } else {
            throw new \Exception("regionid must be a number");
        }
        if ($this->region==10000002) {
            $this->region='forge';
        }
    }

    public function returnPrice($typeid, $regionid = null)
    {
        if (isset($this->cache)) {
            $cacheprice=$this->cache->checkPrice($typeid, $regionid ?: $this->region);
            if (isset($cacheprice)) {
                return $cacheprice;
            }
        }
        $prices=$this->priceSource->returnPrice($typeid, $regionid ?: $this->region);
        if (isset($this->cache)) {
            $this->cache->setPrice($typeid, $regionid ?: $this->region, $prices);
        }
        return $prices;
    }

    public function returnPriceArray($typeids, $regionid = null)
    {
        $cachedpricearray=array();
        if (isset($this->cache)) {
            list($cachedpricearray, $typeids)=$this->cache->checkPriceArray($typeids, $regionid ?: $this->region);
            if (empty($typeids)) {
                return $cachedpricearray;
            }
        }
        $pricearray=$this->priceSource->returnPriceArray($typeids, $regionid ?: $this->region);
        if (isset($this->cache)) {
            $this->cache->setPriceArray($pricearray, $regionid ?: $this->region);
            $totalpricearray=$pricearray+$cachedpricearray;
            $pricearray=$totalpricearray;
        }
        return $pricearray;
    }
    
    public function populateArray($inputarray, $regionid = null)
    {
        $cachedpricearray=array();
        if (isset($this->cache)) {
            list($cachedpricearray, $inputarray)=$this->cache->checkPopulatedArray($inputarray, $regionid ?: $this->region);
            if (empty($inputarray)) {
                return $cachedpricearray;
            }
        }
        $pricearray=$this->priceSource->populateArray($inputarray, $regionid ?: $this->region);
        if (isset($this->cache)) {
            $this->cache->setPopulatedArray($pricearray, $regionid ?: $this->region);
            $totalpricearray=$pricearray+$cachedpricearray;
            $pricearray=$totalpricearray;
        }
        return $pricearray;
    }
}
