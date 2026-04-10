<?php
declare(strict_types=1);
function paginate(int $total, int $per=15, ?int $req=null): array {
    $per   = max(1,$per); $pages = max(1,(int)ceil($total/$per));
    $page  = max(1,min((int)($req??($_GET['page']??1)),$pages));
    $offset= ($page-1)*$per;
    return ['total_rows'=>$total,'per_page'=>$per,'page'=>$page,'total_pages'=>$pages,'offset'=>$offset];
}
function pagination_html(array $p, string $url): string {
    if ($p['total_pages']<=1) return '';
    $h = '<nav class="pagination">';
    $h .= $p['page']>1 ? '<a class="page-btn" href="'.sprintf($url,$p['page']-1).'">‹ Prev</a>' : '<span class="page-btn page-btn--disabled">‹ Prev</span>';
    $s=max(1,$p['page']-2); $e=min($p['total_pages'],$p['page']+2);
    if($s>1) $h.='<span class="page-ellipsis">…</span>';
    for($i=$s;$i<=$e;$i++) $h.='<a class="page-btn'.($i===$p['page']?' page-btn--active':'').'" href="'.sprintf($url,$i).'">'.$i.'</a>';
    if($e<$p['total_pages']) $h.='<span class="page-ellipsis">…</span>';
    $h .= $p['page']<$p['total_pages'] ? '<a class="page-btn" href="'.sprintf($url,$p['page']+1).'">Next ›</a>' : '<span class="page-btn page-btn--disabled">Next ›</span>';
    return $h.'</nav>';
}
