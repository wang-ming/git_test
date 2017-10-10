<?php
namespace app\index\controller;

use app\common\controller\HomeBase;
use app\common\controller\GoodsHandle;
use app\common\controller\UserHandle;
use app\common\model\Goods as GoodsModel;
use app\common\model\Imgs as ImgsModel;
use app\index\model\Places as PlacesModel;
use app\index\model\PlacesGoods as PlacesGoodsModel;
use app\common\model\UserFabulous as UserFabulousModel;
use app\common\model\UserCollect as UserCollectModel;
use app\common\model\Decade as DecadeModel;
use app\common\model\Goodcate as GoodcateModel;
use think\Db;

class Index extends HomeBase
{
    protected $goods_handle;
    protected $user_handle;
    protected $goods_model;
    protected $imgs_model;
    protected $user_fabulous;
    protected $user_collect;
    protected $decade_model;
    protected $goodcate_model;
    protected $places_model;
    protected $places_goods_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->goods_handle = new GoodsHandle();
        $this->user_handle = new UserHandle();
        $this->goods_model = new GoodsModel();
        $this->imgs_model = new ImgsModel();
        $this->places_model = new PlacesModel();    //专场表
        $this->places_goods_model = new PlacesGoodsModel();
        $this->user_fabulous_model = new UserFabulousModel();
        $this->user_collect_model = new UserCollectModel();
        $this->decade_model = new DecadeModel();
        $this->goodcate_model = new GoodcateModel();
    }
    
    public function index(){
        //热门单品 (以查看次数排序)
        $psp_lists = $this->goods_model->alias('t1')->where(['t1.hot'=>1,'t1.genre'=>1,'t1.status'=>['not in','-1,0,2']])
                ->join('ky_imgs t2','t2.row_id=t1.id and t2.table="goods" and t2.field="thumb"','LEFT')
                ->order('t1.look_count desc')
                ->field('t1.id,t1.title,t2.url as good_thumb')
                ->limit(0,6)
                ->select();
        if(!empty($psp_lists)) {
            //截取字段长度
            $psp_lists = substrArr($psp_lists, [['field'=>'title','length'=>10]], $char = 'utf-8');
        }
        
        //单品推荐
        $spr_lists = $this->goods_model->alias('t1')->where(['t1.recommend'=>1,'t1.genre'=>1,'t1.status'=>['not in','-1,0,2']])
                ->join('ky_imgs t2','t2.row_id=t1.id and t2.table="goods" and t2.field="thumb"','LEFT')
                ->order('t1.rec_sort desc','t1.create_time desc')
                ->field('t1.id,t1.title,t1.price,t1.look_count,t1.texture_id,t1.goodcate_id,t1.decade_id,t1.city_name,t2.url as good_thumb')
                ->limit(0,2)
                ->select();
        if(!empty($spr_lists)) {
            //得到商品的点赞数和评论数
            $spr_lists = $this->goods_handle->getColAndFab($spr_lists, 'goods');
            //得到商品的分类和年代
            $spr_lists = $this->goods_handle->getGoodsLabel($spr_lists);
            //截取字段长度
            $spr_lists = substrArr($spr_lists, [['field'=>'title','length'=>10]], $char = 'utf-8');
        }
        
        //竞买推荐
        $bid_lists = $this->goods_model->alias('t1')->where(['t1.recommend'=>1,'t1.genre'=>2,'t1.status'=>['not in','-1,0,2']])
                ->join('ky_imgs t2','t2.row_id=t1.id and t2.table="goods" and t2.field="thumb"','LEFT')
                ->order('t1.rec_sort desc','t1.create_time desc')
                ->field('t1.id,t1.title,t1.now_price,t1.look_count,t1.texture_id,t1.goodcate_id,t1.decade_id,t1.start_time,t1.end_time,t1.status,t1.city_name,t2.url as good_thumb')
                ->limit(0,3)
                ->select();
        if(!empty($bid_lists)) {
            //得到商品的点赞数和评论数
            $bid_lists = $this->goods_handle->getColAndFab($bid_lists, 'goods');
            //得到商品的分类和年代
            $bid_lists = $this->goods_handle->getGoodsLabel($bid_lists);
            //截取字段长度
            $bid_lists = substrArr($bid_lists, [['field'=>'title','length'=>10]], $char = 'utf-8');
        }
        
        $order = [
            't1.rec_sort'   =>  'desc',
            't1.create_time'=>  'desc',
        ];
        //专场列表
        $places_lists = $this->places_model->alias('t1')->where(['t1.status'=>['not in','-1,-2,-3,3'],'t1.recommend'=>1])
                ->join('ky_imgs t2','t2.row_id=t1.id and t2.field="thumb" and t2.table="places"','LEFT')
                ->field('t1.*,t2.url as places_thumb')
                ->order($order)
                ->limit(0,3)
                ->select();
        if(!empty($places_lists)) {
            //获得用户ID集
            $user_ids = implode(array_unique(getFieldArr($places_lists, ['user_id'])['user_id']), ',');
            //得到用户数组
            $users = $this->user_handle->getUserInfo($user_ids);
            $users = valueChangeKey($users, 'id');
            //合并数组
            $places_lists = listComBindList($places_lists, $users, ['headurl'=>'headurl','nickname'=>'nickname'], 'user_id');
            //获得专场ID集
            $places_ids = implode(array_unique(getFieldArr($places_lists, ['id'])['id']), ',');
            //查询拍品数量
            $places_goods_count = $this->places_goods_model->where(['places_id'=>['in',$places_ids]])->group('places_id')
                    ->field('places_id,count(id) as goods_count')
                    ->select();
            $places_goods_count = valueChangeKey($places_goods_count, 'places_id');
            $places_lists = listComBindList($places_lists, $places_goods_count, ['goods_count'=>'goods_count']);
            //截取字段长度
            $places_lists = substrArr($places_lists, [['field'=>'title','length'=>15]], $char = 'utf-8');
        }
        
        $this->assign(['spr_lists'=>$spr_lists, 'psp_lists'=>$psp_lists, 'bid_lists'=>$bid_lists, 'places_lists'=>$places_lists]);
        
        return $this->fetch('index', ['titlename'=>'首页', 'footer'=>1]);
    }
    
    
    
}
