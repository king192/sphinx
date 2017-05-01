<?php
namespace king192\sphinx;
use king192\sphinx\sphinx\sphinxClient;
if (!class_exists('sphinxClient')) {
	new \king192\sphinx\sphinx\configSphinx();
}

// 		echo 'ttt';
// 		echo SPH_RANK_BM25;
// 		exit;
class sphinx {
	protected $options = array(
	'q' => "ttty",
	'sql' => "",
	'mode' => SPH_MATCH_ALL,
	'host' => "127.0.0.1",
	'port' => 9312,
	'index' => "*",
	'groupby' => "",
	'groupsort' => "@group desc",
	'filter' => "group_id",
	'filtervals' => array(),
	'distinct' => "",
	'sortby' => "",
	'sortexpr' => "",
	'limit' => 20,
	'offset' => 0,
	'ranker' => SPH_RANK_PROXIMITY_BM25,
	'select' => "",
	);
	protected $cl;
	public function __construct(array $options){
		// import('think.sphikjhnx.sphinx.const');
		if (!class_exists('sphinxClient')) {
			$this->cl = new sphinxClient ();
		} else {
			$this->cl = new \sphinxClient();
		}
		$this->options = array_merge($this->options,$options);
		// dump($this->options);
	}
////////////
// do query
////////////
	public function doQuery(){
		$this->cl->SetServer ( $this->options['host'], $this->options['port'] );
		$this->cl->SetConnectTimeout ( 1 );
		$this->cl->SetArrayResult ( true );
		$this->cl->SetMatchMode ( $this->options['mode'] );
		if ( count($this->options['filtervals']) )	$this->cl->SetFilter ( $this->options['filter'], $this->options['filtervals'] );
		if ( $this->options['groupby'] )				$this->cl->SetGroupBy ( $this->options['groupby'], SPH_GROUPBY_ATTR, $this->options['groupsort'] );
		if ( !empty($this->options['sortby']) )				$this->cl->SetSortMode ( SPH_SORT_EXTENDED, $this->options['sortby'] );
		if ( !empty($this->options['sortexpr']) )			$this->cl->SetSortMode ( SPH_SORT_EXPR, $this->options['sortexpr'] );
		if ( $this->options['distinct'] )			$this->cl->SetGroupDistinct ( $this->options['distinct'] );
		if ( $this->options['select'] )				$this->cl->SetSelect ( $this->options['select'] );
		if ( $this->options['limit'] )				$this->cl->SetLimits ( $this->options['offset'], $this->options['limit'], ( $this->options['limit']>1000 ) ? $this->options['limit'] : 1000 );
		$this->cl->SetRankingMode ( $this->options['ranker'] );
		$res = $this->cl->Query ( $this->options['q'], $this->options['index'] );
		return $res;
	}
	public function api(){
		return $this->cl;
	}
////////////////
// print me out
////////////////
	public function test(){
		$res = $this->doQuery();
		if ( $res===false )
		{
			print "Query failed: " . $this->cl->GetLastError() . ".\n";

		} else
		{
			if ( $this->cl->GetLastWarning() )
				print "WARNING: " . $this->cl->GetLastWarning() . "\n\n";

			print "Query '$this->options['q']' retrieved $res[total] of $res[total_found] matches in $res[time] sec.\n";
			print "Query stats:\n";
			if ( is_array($res["words"]) )
				foreach ( $res["words"] as $word => $info )
					print "    '$word' found $info[hits] times in $info[docs] documents\n";
			print "\n";

			if ( is_array($res["matches"]) )
			{
				$n = 1;
				print "Matches:\n";
				foreach ( $res["matches"] as $docinfo )
				{
					print "$n. doc_id=$docinfo[id], weight=$docinfo[weight]";
					foreach ( $res["attrs"] as $attrname => $attrtype )
					{
						$value = $docinfo["attrs"][$attrname];
						if ( $attrtype==SPH_ATTR_MULTI || $attrtype==SPH_ATTR_MULTI64 )
						{
							$value = "(" . join ( ",", $value ) .")";
						} else
						{
							if ( $attrtype==SPH_ATTR_TIMESTAMP )
								$value = date ( "Y-m-d H:i:s", $value );
						}
						print ", $attrname=$value";
					}
					print "\n";
					$n++;
				}
			}
		}
	}
}
?>