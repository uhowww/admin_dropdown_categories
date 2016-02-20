<?php 
/*
Plugin Name: Admin DropDown Categories
Plugin URI: http://www.firstelement.jp/
Description: 投稿時のカテゴリ選択をドロップダウンで行える。
Author: FirstElement
Version: 0.1.0
Author URI: http://www.firstelement.jp/
*/

/*  Copyright 2012 Takumi Kumagai (email : kumagai.t at firstelement.jp)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

load_plugin_textdomain( 'admin_dropdown_categories', false, basename(dirname(__FILE__)).DIRECTORY_SEPARATOR."languages" );
/* 投稿ページにフォームを作成するフック */
add_action('admin_menu', 'ADC_add_custom_box');
/* 保存処理 */
add_action('save_post', 'ADC_save_postdata');
/* Ajax用の結果を返すやつ */
add_filter('init','my_category_retrun_child');


/********************************************
/* 投稿ページにブロックを追加
/********************************************/
function ADC_add_custom_box() {
	// Ajaxでフォームを増やすjsコード
	wp_enqueue_script('admin_dropdown_categories',plugin_dir_url( __FILE__ ).'admin_dropdown_categories.js',array('jquery'));
	
	if( function_exists( 'add_meta_box' )) {
		add_meta_box( 'ADC_sectionid', __('Category','admin_dropdown_categories'), 'ADC_inner_custom_box', 'post', 'advanced' );
  }
}

/********************************************
/* メイン処理。カテゴリ取得&フォーム作成
/********************************************/
function ADC_inner_custom_box($post) {
	
	//認証に nonce を使う
	echo '<input type="hidden" name="ADC_noncename" id="ADC_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
	
	
	// 登録済カテゴリ取得。末端カテゴリ抽出
	//$ancestors_cates:登録されている末端カテゴリからトップ親カテゴリまでを集約した配列
	$registered_cates = wp_get_post_categories($post->ID);
	$ancestors_cates = array();
	foreach($registered_cates as $cat){
		$flag = get_term_children($cat,'category');
		if(empty( $flag )){
		//if(get_category_children($cat) == ''){
			$local = get_ancestors( $cat, 'category' );
			array_unshift($local,$cat);
			$ancestors_cates[] = array_reverse($local);
		}
	}
	
	// 追加用のフォームの隠しデータ
	echo '<div id="admin_dropdown_categories_base" style="display:none;">'; 
	$defolt_cates = get_categories('parent=0&hide_empty=0');
	foreach($defolt_cates as $defo_cate){
		echo '<option value="' .$defo_cate->term_id.'">' .$defo_cate->name. '</option>';
	}
	echo '</div>';
	
	
	// ドロップダウン作成
	// 新規投稿用
	if( empty($registered_cates) or ($registered_cates[0] =='false') ){
		echo '<div id="admin_dropdown_categories_'. 0 .'" class="admin_dropdown_categories_' . 0 .'">';
			echo '<select class="0" name="admin_dropdown_categories_[]" onChange="admin_dropdown_categories_next(\''. 0 .'\',\''. 0 .'\')">';
				echo '<option value="false" class="clevel_01" >---未指定---</option>\n';
				foreach($defolt_cates as $defo_cate){
					echo '<option value="' .$defo_cate->term_id .'" class="clevel_01">' .$defo_cate->name. '</option>\n';
				}
			echo '</select>';
		echo '</div>';
		$echo = '<p id="admin_dropdown_categories_append_link"><a href="javascript:void(0)" onclick="make_admin_dropdown_categories_form(0)"> 追加 </a></p>';
		echo $echo;
	}
	// 編集用
	else{
		$i=0;
		foreach($ancestors_cates as $buddy => $cates ){
			echo '<div id="admin_dropdown_categories_'. $i .'" class="admin_dropdown_categories_' . $i .'">';
			$ii=0;
			foreach($cates as $cate ){
				$now_cate = get_categories('include='.$cate);
				$parent_id = $now_cate[0];
				$same_level_cate = get_categories('parent='.$parent_id->parent.'&hide_empty=0');
				echo '<select class="'.$ii.'" name="admin_dropdown_categories_[]" onChange="admin_dropdown_categories_next(\''. $i .'\',\''. $ii .'\')">';
				echo '<option value="false" >---未指定---</option>\n';
				foreach($same_level_cate as $list){
					$selected ='';
					if($list->term_id == $cate){ $selected = 'selected="selected"'; }
					echo '<option value="' .$list->term_id.'" '.$selected.'>' .$list->name. '</option>';
				}
				echo '</select>';
				$ii++;
			}
			echo '</div>';
			$i++;
		}
		$echo = '<p id="admin_dropdown_categories_append_link"><a href="javascript:void(0)" onclick="make_admin_dropdown_categories_form('.($i-1).')"> 追加 </a></p>';
		echo $echo;
	}
}

/*********************************************
/* 保存処理
/*********************************************/
function ADC_save_postdata( $post_id ) {

	if(!isset($_POST['ADC_noncename'])){
		return $post_id;
	}
	// データが先ほど作った編集フォームのから適切な認証とともに送られてきたかどうかを確認。
	if ( !wp_verify_nonce( $_POST['ADC_noncename'], plugin_basename(__FILE__) )) {
		return $post_id;
	}
	
	// 自動保存ルーチンかどうかチェック。そうだった場合はフォームを送信しない（何もしない）
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
		return $post_id;
	}
	
	// パーミッションチェック
	if ( 'page' == $_POST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) )
			return $post_id;
	} else {
		if ( !current_user_can( 'edit_post', $post_id ) )
			return $post_id;
	}
	
	// 承認ができたのでデータを最適化
	$add_cate = array();
	if(isset($_POST['admin_dropdown_categories_']) ){ 
		foreach( $_POST['admin_dropdown_categories_'] as $cate_id){
			if(($cate_id!='false')&&(isset($cate_id))){
				$add_cate[] = $cate_id;
				}
		}
	}
	// 保存
	if($add_cate[0]!='false'){
		wp_set_post_categories($post_id,$add_cate);
	}

	return $post_id;
}

/********************************************
/* jsonで結果を返す
/* GETで得たIDを元に子カテゴリを返す
/********************************************/
function my_category_retrun_child($parent_id = 0){
	if(isset($_GET['admin_dropdown_categories_parent']) && ($_GET['admin_dropdown_categories_parent'] != null)){
		$parent_id = $_GET['admin_dropdown_categories_parent'];
		$list = get_categories('parent='.$parent_id);
		$retrun_list = array();

		foreach($list as $key => $val ){
			if($val->parent == $parent_id){
				$retrun_list[] = array('name' => $val->name , 'id' => $val->term_id);
		}	}
		if(1 > count($retrun_list)){$retrun_list = false;}

		@header('Content-Type: application/json; charset='. get_bloginfo('charset'));
		
		echo json_encode($retrun_list);
		exit;
	}
}
?>