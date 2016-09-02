<?php
namespace App\Modules\Main\Models;
use App\Core\Models\CoreModel as Model;


class Keywords extends Model
{
	protected $table = 'keywords_aws';

    public function getFirstNotComplete($processId=null)
    {
        $query = $this->where("complete",0);
        if ($processId) {
            $query = $query->where("process_id",$processId);
        }
        return $query->first();
    }
    public function getById($id=null)
    {
        return $this->find($id);
    }
    public function getPage($page=null)
    {
        return $this->where("current_page",$page)->first();
    }
    public function completeById($id)
    {
        // return $this->updateData([ "complete"=> 1 ],["id"=>$id]);
        $keyword = $this->find($id);
        $keyword->complete = 1;
        return $keyword->save();
    }
    public function completeTransById($id)
    {
    	// return $this->updateData([ "complete_trans"=> 1 ],["id"=>$id]);
        $keyword = $this->find($id);
    	$keyword->complete_trans = 1;
    	return $keyword->save();
    }
    public function markErrorTransById($id)
    {
        // return $this->updateData([ "complete_trans"=> 1 ],["id"=>$id]);
        $keyword = $this->find($id);
        $keyword->complete_trans = -1;
        return $keyword->save();
    }

    public function markErrorById($id)
    {
        // return $this->updateData([ "complete"=> -1 ],["id"=>$id]);
        $keyword = $this->find($id);
        $keyword->complete = -1;
        return $keyword->save();
    }
    public function updatePage($id,$pageInt)
    {
        // return $this->updateData([ 
        //     "current_page"=> $pageInt ,
        //     "current_page_item"=> 0 ,
        // ],["id"=>$id]);
        $keyword = $this->find($id);
        $keyword->current_page = $pageInt;
        $keyword->current_page_item = 0;
        return $keyword->save();
    }
    public function updatePageTrans($id,$pageInt)
    {
        // return $this->updateData([ "current_page_in_trans"=> $pageInt ],["id"=>$id]);
    	
        $keyword = $this->find($id);
    	$keyword->current_page_in_trans = $pageInt;
    	return $keyword->save();
    }

    public function getFirstNotCompleteTrans($processId=null)
    {
        $query = $this->where("complete_trans",0);
        if (!empty($processId)) {
            $query = $query->where("process_id",$processId);
        }
        return $query->first();
    }
    public function updatePageItem($id,$pageIntItem)
    {
        // return $this->updateData([ "current_page_item"=> $pageIntItem ],["id"=>$id]);
    	
        $keyword = $this->find($id);
    	$keyword->current_page_item = $pageIntItem;
    	return $keyword->save();
    }
    public function updatePageProductStock($id,$cant)
    {
        // $cant = $keyword->total + $cant;
        // return $this->updateData([ "total"=> $cant ],["id"=>$id]);
        $keyword = $this->find($id);
        $keyword->total += $cant;
        return $keyword->save();
    }
    public function setComment($id,$text)
    {
        // return $this->updateData([ "comment"=> $text ],["id"=>$id]);
        $keyword = $this->find($id);
        $keyword->comment = $text;
        return $keyword->save();
    }
    public function wait($second)
    {
        // DB::select("SELECT sleep({$second})");
        sleep($second);
    }
}