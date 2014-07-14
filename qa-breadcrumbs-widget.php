<?php
/*
      Question2Answer (c) Gideon Greenspan
      Google Plus Badge (c) Amiya Sahu (developer.amiya@outlook.com)
      
      http://www.question2answer.org/

      
      File: qa-plugin/basic-adsense/qa-plugin.php
      Version: See define()s at top of qa-include/qa-base.php
      Description: Initiates Adsense widget plugin


      This program is free software; you can redistribute it and/or
      modify it under the terms of the GNU General Public License
      as published by the Free Software Foundation; either version 2
      of the License, or (at your option) any later version.
      
      This program is distributed in the hope that it will be useful,
      but WITHOUT ANY WARRANTY; without even the implied warranty of
      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
      GNU General Public License for more details.

      More about this license: http://www.question2answer.org/license.php
*/
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
           header('Location: ../../');
           exit;
}
           
class q2a_breadcrumbs_widget {


      function allow_template($template)
      {
            return ($template!='admin');
      }

      function allow_region($region) {
            $allow = false;
            switch ($region) {
                  case 'main':
                  case 'full':
                        $allow = true;
                        break;
            }
            return $allow;
      }

      function navigation() {
            $request = qa_request_parts();
            if ( !empty($request) && is_array($request)) return $request;
      }

      function output_widget($region, $place, $themeobject, $template, $request, $qa_content) {
            require_once AMI_BREADCRUMBS_DIR . '/qa-breadcrumbs-admin.php' ;
            $widget_opt = qa_get_options(array(q2a_breadcrumbs_admin::SHOW_HOME , q2a_breadcrumbs_admin::TRUNCATE_LENGTH));
			
            // breadcrumb start
            $themeobject->output('<ul class="breadcrumb clearfix">');
            if ($widget_opt[q2a_breadcrumbs_admin::SHOW_HOME]) {
                  $themeobject->output($this->breadcrumb_part(array('type' => 'home')));
            }
            $themeobject->output($this->create_breadcrumbs($this->navigation(), $qa_content , $widget_opt, $template) );
            $themeobject->output('</ul>');
       }

      function create_breadcrumbs($navs, $qa_content , $widget_opt, $template ) {
			
            $br = "";
            $question_page = @$qa_content['q_view'];
		if($template == 'not-found')
			$br .=$this->breadcrumb_part(array(
					  'type' => 'not-found',
					  'url' => '/',
					  'text' => qa_lang('breadcrumbs/not_found'),
				  ));
            elseif (!!$question_page) {     //if it is a question page 
                  // category is the first priority 
                  $cat = @$question_page['where'];
                  $tags = @$question_page['q_tags'];
                  if (!!$cat) {
                        $categoryids = @$qa_content['categoryids'];
                        if (!!$categoryids) {
                              foreach ($categoryids as $categoryid) {
                                    $category_details = $this->get_cat($categoryid);
                                    if (is_array($category_details) && !empty($category_details)) {
							$backpath = $category_details['backpath'];
							$text     = $category_details['title'];
							$url      = $this->cat_path($backpath);
                                          $data = array(
								'type' => 'cat',
								'text' => $text,
								'url'  => $url,
                                          );
                                          $br .=$this->breadcrumb_part($data);
                                    }
                              }
                        }
                  }else { //if question is asked with out any categories list all the tags
                        $br .=$this->breadcrumb_part(array(
                            'type' => 'questions',
                            'url' => qa_path_html('questions'),
                            'text' => qa_lang('breadcrumbs/questions'),
                        ));
                  }

                  $q_title = $qa_content['q_view']['raw']['title'] ;
                  $q_id = $qa_content['q_view']['raw']['postid'] ; 
                  $trunc_len = $widget_opt[q2a_breadcrumbs_admin::TRUNCATE_LENGTH];
                  if ($trunc_len <= 0 ) {
                       $trunc_len = strlen($q_title) ;
                  }
                  $br .=$this->breadcrumb_part(array(
                      'type' => 'questions',
                      'url' =>  qa_q_path($q_id, $q_title, true) ,
                      'text' => $this->truncate( $q_title, $trunc_len ),
                  ));
            } else {  //means non questions page 
                  if (count($navs) > 0) {
                        $link = "";
                        $type = $navs[0];
                        if (!$type) {
                        	return ; //if there is not a single part -- go back from here 
                        }
                        $translate_this_arr = array("questions","unanswered","tags","tag" ,"users","user", "categories", "ask");
                        foreach ($navs as $nav) {
                              
					$link .= (!!$link) ? "/" . $nav : $nav;
                              // added this to fix users page bug and tag page bug 
                              $prev_link =  $link ;
                              $link = ($link === "user") ? "users" : $link ;
                              $link = ($link === "tag")  ? "tags"  : $link ;
                              $text = (in_array($nav, $translate_this_arr)) ? qa_lang("breadcrumbs/".$nav) : ucwords($nav) ;
					$br   .= $this->breadcrumb_part(array(
						'type' => $type,
						'url'  => qa_path($link),
						'text' => $text,
                              ));
                              // reset the link for next iteration 
                              $link = $prev_link ;
                        }

                        switch ($type) {
                              case 'unanswered':
                                    $by = qa_get('by');
                                    if (!$by) {
                                          $br .= $this->breadcrumb_part(array(
								'type' => 'no-ans',
								'url'  => qa_path($link),
								'text' => qa_lang('breadcrumbs/no_ans'),
                                          ));
                                    } else if ($by === 'selected') {
                                          $br .= $this->breadcrumb_part(array(
      							'type' => 'no-selected',
      							'url'  => qa_path($link) . '?by=selected',
      							'text' => qa_lang('breadcrumbs/no_selected_ans'),
                                          ));
                                    } else if ($by === 'upvotes') {
                                          $br .= $this->breadcrumb_part(array(
								'type' => 'no-upvots',
								'url'  => qa_path($link) . '?by=upvotes',
								'text' => qa_lang('breadcrumbs/no_upvoted_ans'),
                                          ));
                                    }

                                    break;
                              case 'questions':
                                    $sort = qa_get('sort');
                                    if (!$sort) {
                                          $br .= $this->breadcrumb_part(array(
								'type' => 'q-sort-recent',
								'url'  => qa_path($link),
								'text' => qa_lang('breadcrumbs/recent_que'),
                                          ));
                                    } else if ($sort === 'hot') {
                                          $br .= $this->breadcrumb_part(array(
								'type' => 'q-sort-hot',
								'url'  => qa_path($link) . '?sort=hot',
								'text' => qa_lang('breadcrumbs/hot'),
                                          ));
                                    } else if ($sort === 'votes') {
                                          $br .= $this->breadcrumb_part(array(
								'type' => 'q-sort-votes',
								'url'  => qa_path($link) . '?sort=votes',
								'text' => qa_lang('breadcrumbs/most_votes'),
                                          ));
                                    } else if ($sort === 'answers') {
                                          $br .= $this->breadcrumb_part(array(
      							'type' => 'q-sort-answers',
      							'url'  => qa_path($link) . '?sort=answers',
      							'text' => qa_lang('breadcrumbs/most_answers'),
                                          ));
                                    } else if ($sort === 'views') {
                                          $br .= $this->breadcrumb_part(array(
								'type' => 'no-sort-views',
								'url'  => qa_path($link) . '?sort=views',
								'text' => qa_lang('breadcrumbs/most_views'),
                                          ));
                                    }
                                    break;
                              default:
                                    break;
                        }
                  }
            }

            return $br;
      }

      function breadcrumb_part($data) {
            if (!$data) {
                  return;
            }
            $li_template = "<li ^class><a href='^url'>^icon^text</a></li>";
            $type = !empty($data['type']) ? $data['type'] : "";
            $text = !empty($data['text']) ? $data['text'] : "";
            $url  = !empty($data['url'])  ? $data['url'] : "#";
            $icon = '';
            $class = "";
            // $text = qa_lang("breadcrumbs/$text");
            switch ($type) {
                  case 'home':
				$url   = qa_opt('site_url');
                        $text  = qa_lang("breadcrumbs/home");
                        $class = "class='cs-breadcrumbs-home'";
                        /*$icon  = "<i class='icon-home'></i> ";*/
                        break;
                  case 'cat':
                  case 'categories':
                        $class = "class='cs-breadcrumbs-cat'";
                        break;
                  case 'q_tag':
                        $li_template = "<li ^class>^text</li>";
                        $class = "class='cs-breadcrumbs-tag'";
                        break;
                 
                  default:
                        $class = "class='cs-breadcrumbs-$type'";
                        break;
            }
            return strtr($li_template, array(
                '^class' => $class,
                '^url'   => $url,
                '^icon'  => $icon,
                '^text'  => $text,
            ));
      }

      function get_cat($cat_id = "") {
            require_once QA_INCLUDE_DIR . "/qa-db-selects.php";
            if (!$cat_id) 
                  return;

            return (qa_db_select_with_pending(qa_db_full_category_selectspec($cat_id, true)));
      }

      function cat_path($categorybackpath){
            return qa_path_html(implode('/', array_reverse(explode('/', $categorybackpath))));
      }

      function truncate($string, $limit, $pad="...") {
            if(strlen($string) <= $limit) 
                  return $string; 
            else{ 
                  $text = $string.' ';
                  $text = substr($text,0,$limit);
                  $text = substr($text,0,strrpos($text,' '));
                  return $text.$pad;
            } 
      }
}

/*

	Omit PHP closing tag to help avoid accidental output

*/