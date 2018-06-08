<?php

/**
 *
 * 分页系统
 *
 * @author flybug
 * @version 2.1.1
 *
 * 增加了分页后信息的返回2013-03-06
 *
 */
class divpage
{
    protected $baseurl = ''; //基本链接
    protected $menustyle = 1;
    protected $sql = '';
    protected $pagenow = 1; //当前页数
    protected $pagesize = 10; //页面大小
    protected $pagemenu = ''; //菜单样式
    protected $pagedata = array();
    protected $resulttype = PDO::FETCH_ASSOC; //使用字段作为索引的返回值
    protected $fields = '*'; //字段
    protected $pageinfo = ''; //当前页面的相关信息
    protected $root = ''; //翻页后的数据挂节点
    protected $orderstr = '';//排序（子查询后，排序必须强制指定，否则可能会丢失）
    protected $wherestr = '';//筛选
    protected $scriptFunc = '';//翻页动作函数


    public function __construct($sql, $model = '', $fields = '*', $pagenow = 1, $pagesize = 0, $menustyle = 1, $root = '', $scriptFunc = 'ago', $order = '', $where = '')
    {
        $pagenow = !is_numeric($pagenow) ? 1 : $pagenow;
        $pagenow = $pagenow < 1 ? 1 : $pagenow;
        $this->sql = $sql; //sql查询语句
        $this->pagesize = ($pagesize == 0) ? PAGESIZE : $pagesize; //长度
        $this->pagenow = $pagenow; //现在的页面 从第几页开始计数
        $this->baseurl = ($model == '') ? '?' : "?model={$model}&"; //模板
        $this->fields = $fields; //字段
        $this->root = $root;
        $this->orderstr = $order;
        $this->wherestr = $where;
        $this->menustyle = $menustyle;
        $this->scriptFunc = $scriptFunc;
    }

    //得到分页数据（优化分页核心Sql）2013-03-27 by flybug
    public function getDivPage()
    {
        //起止号
        $_beginRow = ($this->pagenow - 1) * $this->pagesize; //所在页数-1*页面显  示数=当前页记录开始的数
        //$_endRow = $_beginRow + $this->pagesize;//此处修正了取记录的数量。修正了limit参数的第二个为每页数量
        $_limitString = " LIMIT {$_beginRow},{$this->pagesize}"; //从当前页开始数的地方取页面显示数量的记录（limit选取）
        //排序字段
        $_orderString = ($this->orderstr == '') ? '' : " $this->orderstr"; //oeder的条件排序


        $sql = "SELECT SQL_CALC_FOUND_ROWS {$this->fields} FROM ($this->sql) a{$_orderString}{$_limitString}";
        //$sql = "SELECT SQL_CALC_FOUND_ROWS $this->fields FROM ($this->sql) a WHERE id >= (SELECT id FROM table LIMIT $_beginRow, 1) LIMIT $this->pagesize";
        $db = new MySql();
        $db->getAll($sql); //执行sql语句
        //echo $sql;
        //得到页数据
        $count = $db->getTotalRow(); //返回记录的总行数
        $this->pagedata = $db->getAllRecodesEx($this->resulttype); //返回数据
        //得到导航菜单
        $lastpage = ceil($count / $this->pagesize); //向上舍入为整数  将行数划断
        /* 2013-3-28日修改，小白：当记录为空时，最后页数为零；翻页失败 */
        $lastpage = $lastpage == 0 ? 1 : $lastpage;

        $this->pageinfo = array(
            "msg" => "您要查找的记录",
            "total" => $count, //总记录数
            "pagenow" => $this->pagenow, //当前页的值
            "fpage" => 0 < $this->pagenow - 1 ? $this->pagenow - 1 : 1, //上一页
            "npage" => $lastpage < $this->pagenow + 1 ? $lastpage : $this->pagenow + 1, //下一页
            "lastpage" => $lastpage, //最后一页
            "pagecount" => $lastpage, //页面数量
            "pagesize" => $this->pagesize, //页面容量（当前页面显示的记录数）
            "root" => $this->root
        );
        $_para = array(//需要替换的内容
            "_replace" => $this->pageinfo
        );
        $this->setMenuStyle($this->menustyle, $this->scriptFunc); //设置显示样式
        if ($this->menustyle == 0) {
            $this->getListStyleMenu();
        } else {
            $myhtml = new myHTML(); //新的myhtml类（class_myHTML）
            $this->pagemenu = $myhtml->getHTML($this->pagemenu, $_para); //加载参数			
        }
    }

    //导航条
    public function getMenu()
    {
        return $this->pagemenu;
    }

    //得到页面数据
    public function getPage()
    {
        return $this->pagedata;
    }

    public function getPageInfo()
    {
        return $this->pageinfo;
    }

    public function setPageNow($cp = 1)
    {//当前所在页
        $this->pagenow = $cp;
    }

    public function getPageAndMenu()
    {
        return array('menu' => $this->pagemenu, 'page' => $this->pagedata);
    }

    //计算列表样式的分页菜单
    public function getListStyleMenu()
    {
        $t = floor(($this->pageinfo['pagenow'] - 1) / 10) * 10 + 1; //向下舍入，floor(1.9)=1
        for ($i = $t; $i < min(($t + 10), $this->pageinfo['lastpage'] + 1); $i++) {
            if ($i == $this->pageinfo['pagenow']) {
                $this->pagemenu .= "<a class=\"current\" href=\"javascript:go($i)\" target=\"_self\">$i</a>";
            } else {
                $this->pagemenu .= "<a href=\"javascript:go($i)\" target=\"_self\">{$i}</a>";
            }
        }

        if ($i > 11) {
            $p = $t - 1;
            $this->pagemenu = "<a class=\"ellipsis\" href=\"javascript:go($p)\" target=\"_self\">……</a>" . $this->pagemenu;
        }
        $this->pagemenu = '<a href="javascript:go(' . $this->pageinfo["fpage"] . ')" target="_self">上一页</a>' . $this->pagemenu;
        if ($i < $this->pageinfo['lastpage']) {
            $p = $t + 10;
            $this->pagemenu .= "<a class=\"ellipsis\" href=\"javascript:go($p)\" target=\"_self\">……</a><a>{$this->pageinfo['lastpage']}</a>";
        }
        $this->pagemenu .= '<a href="javascript:go(' . $this->pageinfo["npage"] . ')" target="_self">下一页</a>';
    }

    //设置样式导航条	
    public function setMenuStyle($ms = 1, $scriptFunc)
    {
        $this->menustyle = $ms;
        switch ($this->menustyle) {
            case 1 :
                $this->pagemenu = "<br /><style><!--";
                $this->pagemenu .= ".menu{BORDER-TOP: #cccccc 1px solid;BORDER-BOTTOM: #cccccc 1px solid;BACKGROUND-COLOR: #f2f2f2;padding:4px;}";
                $this->pagemenu .= "//--></style>";
                $this->pagemenu .= "<flybug:replace value=\"msg|total|pagenow|fpage|npage|lastpage|pagecount|pagesize\">";
                $this->pagemenu .= "<div class=\"menu\">[{msg}] ";
                $this->pagemenu .= "合计{total}条记录 | ";
                $this->pagemenu .= "<a href=\"{$this->baseurl}page=1\">首页</a> ";
                $this->pagemenu .= "<a href=\"{$this->baseurl}page={fpage}\">上一页</a> ";
                $this->pagemenu .= "<a href=\"{$this->baseurl}page={npage}\">下一页</a> ";
                $this->pagemenu .= "<a href=\"{$this->baseurl}page={lastpage}\">尾页</a> | ";
                $this->pagemenu .= "页次：{pagenow}/{pagecount}页 ";
                $this->pagemenu .= "{pagesize}个/页 ";
                $this->pagemenu .= "</flybug>";
                break;
            case 2 :
                $this->pagemenu = "<br /><style><!--";
                $this->pagemenu .= ".menu{BORDER-Left: #375E90 6px solid;padding:4px;}";
                $this->pagemenu .= "//--></style>";
                $this->pagemenu .= "<flybug:replace value=\"msg|total|pagenow|fpage|npage|lastpage|pagecount|pagesize\">";
                $this->pagemenu .= "<div class=\"menu\">";
                $this->pagemenu .= "合计{total}条记录 | <a href=\"{$this->baseurl}page=1\">首页</a> <a href=\"{$this->baseurl}page={fpage}\">上一页</a> <a href=\"{$this->baseurl}page={npage}\">下一页</a> <a href=\"{$this->baseurl}page={lastpage}\">尾页</a> | 页次：{pagenow}/{pagecount}页 {pagesize}个/页";
                $this->pagemenu .= "</div>";
                $this->pagemenu .= "</flybug>";
                break;
            case 3 :
                $this->pagemenu = "<flybug:replace value=\"msg|total|pagenow|fpage|npage|lastpage|pagecount|pagesize\">";
                $this->pagemenu .= "<p>合计{total}条记录 | <a href=\"{$this->baseurl}page=1\">首页</a> <a href=\"{$this->baseurl}page={fpage}\">上一页</a> <a href=\"{$this->baseurl}page={npage}\">下一页</a> <a href=\"{$this->baseurl}page={lastpage}\">尾页</a> | 页次：{pagenow}/{pagecount}页 {pagesize}个/页</p>";
                $this->pagemenu .= "</flybug>";
                break;
            case 4 :
                $this->pagemenu = "<br /><style><!--";
                $this->pagemenu .= ".menu{BORDER-TOP: #cccccc 1px solid;BORDER-BOTTOM: #cccccc 1px solid;BACKGROUND-COLOR:#f2f2f2;padding:4px;}";
                $this->pagemenu .= "//--></style>";
                $this->pagemenu .= "<flybug:replace value=\"msg|total|pagenow|fpage|npage|lastpage|pagecount|pagesize\">";
                $this->pagemenu .= "<div class=\"menu\">[{msg}] ";
                $this->pagemenu .= "合计{total}条记录 | ";
                $this->pagemenu .= "<a href=\"javascript:void(0);\" target=\"_self\" onClick=\"gopage(1)\">首页</a> ";
                $this->pagemenu .= "<a href=\"javascript:void(0);\" target=\"_self\" onclick=\"gopage({fpage})\">上一页</a> ";
                $this->pagemenu .= "<a href=\"javascript:void(0);\" target=\"_self\" onclick=\"gopage({npage})\">下一页</a> ";
                $this->pagemenu .= "<a href=\"javascript:void(0);\" target=\"_self\" onclick=\"gopage({lastpage})\">尾页</a> | ";
                $this->pagemenu .= "页次：{pagenow}/{pagecount}页 ";
                $this->pagemenu .= "{pagesize}个/页 ";
                $this->pagemenu .= "</flybug>";
                break;
            case 5 :
                $this->pagemenu .= "<flybug:replace value=\"total|pagenow|fpage|npage|lastpage|pagecount|pagesize\">";
                $this->pagemenu .= "<span>";
                $this->pagemenu .= "合计{total}条记录 | ";
                $this->pagemenu .= "<a href=\"{$this->baseurl}page=1\">首页</a> ";
                $this->pagemenu .= "<a href=\"{$this->baseurl}page={fpage}\">上一页</a> ";
                $this->pagemenu .= "<a href=\"{$this->baseurl}page={npage}\">下一页</a> ";
                $this->pagemenu .= "<a href=\"{$this->baseurl}page={lastpage}\">尾页</a> | ";
                $this->pagemenu .= "页次：{pagenow}/{pagecount}页 ";
                $this->pagemenu .= "{pagesize}个/页</span> ";
                $this->pagemenu .= "</flybug>";
                break;
            case 6 :
                $this->pagemenu .= "<flybug:replace value=\"total|pagenow|fpage|npage|lastpage|pagecount|pagesize\">";
                $this->pagemenu .= "<style>#changepage{margin:0px;padding:0px;border:none;color:#666666;background:none;font-size:12px;
				font-weight:700;width:100%;}#changepage a{margin:0px;padding:0px;border:none;color:#666666;
				background:none;font-size:12px;font-weight:700;display:block;float:left;margin-right:5px;
				line-height:20px;text-decoration:none;}</style><div id='changepage'>";
                $this->pagemenu .= "<a href='{$this->baseurl}page=1'>首页</a>";
                $this->pagemenu .= "<a href='{$this->baseurl}page={fpage}'>上一页</a>";
                $this->pagemenu .= "<a href='{$this->baseurl}page={npage}'>下一页</a>";
                $this->pagemenu .= "<a href='{$this->baseurl}page={lastpage}'>尾页</a>";
                $this->pagemenu .= "<a>合计{total}条记录</a>";
                $this->pagemenu .= "<a>页次：{pagenow}/{pagecount}页</a>";
                $this->pagemenu .= "<a>{pagesize}个/页</a></div>";
                $this->pagemenu .= "</flybug>";
                break;
            case 7 :
                $this->pagemenu .= "<flybug:replace value=\"total|pagenow|fpage|npage|lastpage|pagecount|pagesize\">";
                $this->pagemenu .= "<style>#a{font-size:14px;font-weight:700;margin-right:10px;}</style>";
                $this->pagemenu .= "<a id='a' href='{$this->baseurl}page=1'>首页</a>";
                $this->pagemenu .= "<a id='a' href='{$this->baseurl}page={fpage}'>上一页</a>";
                $this->pagemenu .= "<a id='a' href='{$this->baseurl}page={npage}'>下一页</a>";
                $this->pagemenu .= "<a id='a' href='{$this->baseurl}page={lastpage}'>尾页</a>";
                $this->pagemenu .= "<span id='a'>合计{total}条记录</span>";
                $this->pagemenu .= "<span id='a'>页次：{pagenow}/{pagecount}页</span>";
                $this->pagemenu .= "<span id='a'>{pagesize}个/页</span>";
                $this->pagemenu .= "</flybug>";
                break;
            case 8 :
                $this->pagemenu .= "<flybug:replace value=\"total|pagenow|fpage|npage|lastpage|pagecount|pagesize\">";
                $this->pagemenu .= "<span>";
                $this->pagemenu .= "合计{total}条记录 | ";
                $this->pagemenu .= "<a href=\"javascript:" . $scriptFunc . "(1);\" target=\"_self\">首页</a> ";
                $this->pagemenu .= "<a href=\"javascript:" . $scriptFunc . "({fpage});\" target=\"_self\">上一页</a> ";
                $this->pagemenu .= "<a href=\"javascript:" . $scriptFunc . "({npage});\" target=\"_self\">下一页</a> ";
                $this->pagemenu .= "<a href=\"javascript:" . $scriptFunc . "({lastpage});\" target=\"_self\">尾页</a> | ";
                $this->pagemenu .= "页次：{pagenow}/{pagecount}页 ";
                $this->pagemenu .= "{pagesize}个/页</span> ";
                $this->pagemenu .= "</flybug>";
                break;
            case 9 ://ajax翻页，必须定义divroot挂节点
                $this->pagemenu .= "<flybug:replace value=\"total|pagenow|fpage|npage|lastpage|pagecount|pagesize|root\">";
                $this->pagemenu .= "<div class='ajax_pages'>";
                $this->pagemenu .= "合计{total}条记录 | ";
                $this->pagemenu .= "<a href=\"javascript:" . $scriptFunc . "(1,'{root}');\" target=\"_self\">首页</a> ";
                $this->pagemenu .= "<a href=\"javascript:" . $scriptFunc . "({fpage},'{root}');\" target=\"_self\">上一页</a> ";
                $this->pagemenu .= "<a href=\"javascript:" . $scriptFunc . "({npage},'{root}');\" target=\"_self\">下一页</a> ";
                $this->pagemenu .= "<a href=\"javascript:" . $scriptFunc . "({lastpage},'{root}');\" target=\"_self\">尾页</a> | ";
                $this->pagemenu .= "页次：{pagenow}/{pagecount}页 ";
                $this->pagemenu .= "<input type=\"text\" maxlength=\"10\" style=\"line-height:22px;height:22px;width:30px;\"><span style='cursor:pointer;color:#06F;' onclick=\"" . $scriptFunc . "($(this).prev().val(),'{root}');\">前往</span>&nbsp; | ";
                $this->pagemenu .= "{pagesize}个/页</div> ";
                $this->pagemenu .= "</flybug>";
                break;

            case 10 :
                $this->pagemenu .= "<flybug:replace value=\"total|pagenow|fpage|npage|lastpage|pagecount|pagesize\">";
                $this->pagemenu .= "<span>";
                $this->pagemenu .= "合计{total}条记录 </span> ";
                $this->pagemenu .= "<a href=\"{$this->baseurl}page=1\">首页</a> ";
                $this->pagemenu .= "<a href=\"{$this->baseurl}page={fpage}\">上一页</a> ";
                $this->pagemenu .= "<a href=\"{$this->baseurl}page={npage}\">下一页</a> ";
                $this->pagemenu .= "<a href=\"{$this->baseurl}page={lastpage}\">尾页</a>";
                $this->pagemenu .= "<span>页次：{pagenow}/{pagecount}页 ";
                $this->pagemenu .= "{pagesize}个/页</span> ";
                $this->pagemenu .= "</flybug>";
                break;
            case 11 ://ajax翻页，去掉页数 和总数 - 用于 优品试用的报名
                $this->pagemenu .= "<flybug:replace value=\"total|pagenow|fpage|npage|lastpage|pagecount|pagesize|root\">";
                $this->pagemenu .= "<div class='ajax_pages'>";
                $this->pagemenu .= " ";
                $this->pagemenu .= "<a href=\"javascript:" . $scriptFunc . "(1,'{root}');\" target=\"_self\">首页</a> ";
                $this->pagemenu .= "<a href=\"javascript:" . $scriptFunc . "({fpage},'{root}');\" target=\"_self\">上一页</a> ";
                $this->pagemenu .= "<a href=\"javascript:" . $scriptFunc . "({npage},'{root}');\" target=\"_self\">下一页</a> ";
                $this->pagemenu .= "<a href=\"javascript:" . $scriptFunc . "({lastpage},'{root}');\" target=\"_self\">尾页</a> ";
                $this->pagemenu .= "</div> ";
                $this->pagemenu .= "</flybug>";
                break;
            default :
                return;
        }
    }

}

?>
