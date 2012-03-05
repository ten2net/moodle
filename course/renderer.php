<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Renderer for use with the course section and all the goodness that falls
 * within it.
 *
 * This renderer should contain methods useful to courses, and categories.
 *
 * @package   moodlecore
 * @copyright 2010 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The core course renderer
 *
 * Can be retrieved with the following:
 * $renderer = $PAGE->get_renderer('core','course');
 */
class core_course_renderer extends plugin_renderer_base {

    /**
     * A cache of strings
     * @var stdClass
     */
    protected $strings;

    /**
     * Override the constructor so that we can initialise the string cache
     *
     * @param moodle_page $page
     * @param string $target
     */
    public function __construct(moodle_page $page, $target) {
        $this->strings = new stdClass;
        parent::__construct($page, $target);
    }

    /**
     * Renderers a structured array of courses and categories into a nice
     * XHTML tree structure.
     *
     * This method was designed initially to display the front page course/category
     * combo view. The structure can be retrieved by get_course_category_tree()
     *
     * @param array $structure
     * @return string
     */
    public function course_category_tree(array $structure) {
        $this->strings->summary = get_string('summary');

        // Generate an id and the required JS call to make this a nice widget
        $id = html_writer::random_id('course_category_tree');
        $this->page->requires->js_init_call('M.util.init_toggle_class_on_click', array($id, '.category.with_children .category_label', 'collapsed', '.category.with_children'));

        // Start content generation
        $content = html_writer::start_tag('div', array('class'=>'course_category_tree', 'id'=>$id));
        foreach ($structure as $category) {
            $content .= $this->course_category_tree_category($category);
        }
        $content .= html_writer::start_tag('div', array('class'=>'controls'));
        $content .= html_writer::tag('div', get_string('collapseall'), array('class'=>'addtoall expandall'));
        $content .= html_writer::tag('div', get_string('expandall'), array('class'=>'removefromall collapseall'));
        $content .= html_writer::end_tag('div');
        $content .= html_writer::end_tag('div');

        // Return the course category tree HTML
        return $content;
    }

    /**
     * Renderers a category for use with course_category_tree
     *
     * @param array $category
     * @param int $depth
     * @return string
     */
    protected function course_category_tree_category(stdClass $category, $depth=1) {
			//修改的地放引入全局变量 @李明奇  2012.3.2
		global $CFG, $USER, $DB, $OUTPUT;
		//end  以上删除并不影响程序
        $content = '';
        $hassubcategories = (count($category->categories)>0);
        $hascourses = (count($category->courses)>0);
        $classes = array('category');
        if ($category->parent != 0) {
            $classes[] = 'subcategory';
        }
        if (empty($category->visible)) {
            $classes[] = 'dimmed_category';
        }
        if ($hassubcategories || $hascourses) {
            $classes[] = 'with_children';
            if ($depth > 1) {
                $classes[] = 'collapsed';
            }
        }
        $content .= html_writer::start_tag('div', array('class'=>join(' ', $classes)));
        $content .= html_writer::start_tag('div', array('class'=>'category_label'));
        $content .= html_writer::link(new moodle_url('/course/category.php', array('id'=>$category->id)), $category->name, array('class'=>'category_link'));
        $content .= html_writer::end_tag('div');
        if ($hassubcategories) {
            $content .= html_writer::start_tag('div', array('class'=>'subcategories'));
            foreach ($category->categories as $subcategory) {
                $content .= $this->course_category_tree_category($subcategory, $depth+1);
            }
            $content .= html_writer::end_tag('div');
        }
        if ($hascourses) {
            $content .= html_writer::start_tag('div', array('class'=>'courses'));
            $coursecount = 0;
            foreach ($category->courses as $course) {
                $classes = array('course');
                $linkclass = 'course_link';
                if (!$course->visible) {
                    $linkclass .= ' dimmed';
                }
                $coursecount ++;
                $classes[] = ($coursecount%2)?'odd':'even';
                $content .= html_writer::start_tag('div', array('class'=>join(' ', $classes)));
                $content .= html_writer::link(new moodle_url('/course/view.php', array('id'=>$course->id)), format_string($course->fullname), array('class'=>$linkclass));
				  //修改开始  以下为添加的  @李明奇  2012.3.2
                  
				  
				  
			$course = $DB->get_record('course', array('id'=>$course->id));
			$context = get_context_instance(CONTEXT_COURSE, $course->id);
				   if (!empty($CFG->coursecontact)) {
        $managerroles = explode(',', $CFG->coursecontact);
        $namesarray = array();
        if (isset($course->managers)) {    //最外围if 开始
            if (count($course->managers)) {
                $rusers = $course->managers;
                $canviewfullnames = has_capability('moodle/site:viewfullnames', $context);

                 /// Rename some of the role names if needed
                if (isset($context)) {
                    $aliasnames = $DB->get_records('role_names', array('contextid'=>$context->id), '', 'roleid,contextid,name');
                }

                // keep a note of users displayed to eliminate duplicates
                $usersshown = array();
				
                foreach ($rusers as $ra) {

                    // if we've already displayed user don't again
                    if (in_array($ra->user->id,$usersshown)) {
                        continue;
                    }
                    $usersshown[] = $ra->user->id;

                    $fullname = fullname($ra->user, $canviewfullnames);

                    if (isset($aliasnames[$ra->roleid])) {
                        $ra->rolename = $aliasnames[$ra->roleid]->name;
                    }

                    $namesarray[] = format_string($ra->rolename).': '.
                                    html_writer::link(new moodle_url('/user/view.php', array('id'=>$ra->user->id, 'course'=>SITEID)), $fullname);
                }
            }
        } else {
            $rusers = get_role_users($managerroles, $context,
                                     true, '', 'r.sortorder ASC, u.lastname ASC');
            if (is_array($rusers) && count($rusers)) {
                $canviewfullnames = has_capability('moodle/site:viewfullnames', $context);

                /// Rename some of the role names if needed  当在此课程中重命名角色称为的时候
                if (isset($context)) {
                    $aliasnames = $DB->get_records('role_names', array('contextid'=>$context->id), '', 'roleid,contextid,name');
                }
                
                foreach ($rusers as $teacher) {
                    $fullname = fullname($teacher, $canviewfullnames);

                    /// Apply role names
                    if (isset($aliasnames[$teacher->roleid])) {
                        $teacher->rolename = $aliasnames[$teacher->roleid]->name;
                    }
                       
                    $namesarray[] = format_string($teacher->rolename).': '.
                                    html_writer::link(new moodle_url('/user/view.php', array('id'=>$teacher->id, 'course'=>SITEID)), $fullname);
                }
            }
        }
                 //课程负责人教师的调用
        if (!empty($namesarray)) {
		  
            //$content.=html_writer::start_tag('ul', array('class'=>'teachers'));
            foreach ($namesarray as $name) {
                //$content.= html_writer::tag('span', $name);
				 $content.= $name;
            }
           //$content.= html_writer::end_tag('ul');
        }
    }  //最外围if 结束
	
		
   //修改结束  添加结束  以上删除并不影响只是调用本课程教师
                $content .= html_writer::start_tag('div', array('class'=>'course_info clearfix'));

                // print enrol info
                if ($icons = enrol_get_course_info_icons($course)) {
                    foreach ($icons as $pix_icon) {
                        $content .= $this->render($pix_icon);
                    }
                }

                if ($course->summary) {
                    $image = html_writer::empty_tag('img', array('src'=>$this->output->pix_url('i/info'), 'alt'=>$this->strings->summary));
                    $content .= html_writer::link(new moodle_url('/course/info.php', array('id'=>$course->id)), $image, array('title'=>$this->strings->summary));
                }
                $content .= html_writer::end_tag('div');
                $content .= html_writer::end_tag('div');
            }
            $content .= html_writer::end_tag('div');
        }
        $content .= html_writer::end_tag('div');
        return $content;
    }
}
