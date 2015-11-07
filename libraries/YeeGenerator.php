<?php if (! defined('BASEPATH')) exit('No direct script access');
/**
 * @package 		CRUD´s GENERATOR for CodeIgniter
 * @subpackage 		Yee Generator
 * @author 			Antonio Yee || yee.antonio@gmail.com || @antonioyee || antonioyee.mx
 * @license         MIT
 * @link			https://github.com/antonioyee/crud-generator
 */
class YeeGenerator {

	var $script 		= NULL;
	var $table 			= NULL;
	var $field 			= array();
	var $primary_key 	= NULL;

	private function _ValidateRoute($name_sql){
		if ( ! is_dir('./tables/') ) {
			mkdir('./tables/', 0777);
		}

		if ( file_exists('./tables/'.$name_sql) ) {
			return $sql = fopen('./tables/'.$name_sql , "r");
		}else{
			die('The .sql file is not found to create the components');
		}
	}

	public function GeneratingComponents($name_sql){
		$sql = $this->_ValidateRoute($name_sql);

		while( ! feof($sql) ) {
			$this->script .= fgets($sql);
		}

		fclose($sql);

		$this->script = explode(';', $this->script);

		for ($index=0; $index < count($this->script)-1 ; $index++) {

			/** ----------------------------------------------------------------------------------------
			 * Conditions can change depending on the SQL SCRIPT FORMAT
			 * -------------------------------------------------------------------------------------- */
			if ( strpos( $this->script[$index], "DROP TABLE IF EXISTS") ) { // Get name of the table
				$string_line 	= explode('`', $this->script[$index] );
				$this->table 	= $string_line[1];
			}else{
				if ( strpos( $this->script[$index], "CREATE TABLE IF NOT EXISTS") ) {
					$string_line 	= explode('`', $this->script[$index] );
					$this->table 	= $string_line[5];
				}
			}
			// -----------------------------------------------------------------------------------------

			if ( strpos( $this->script[$index], "CREATE TABLE" ) ) { // Get data fields
				$line = explode( "\n", $this->script[$index] );

				for ($row=0; $row < count($line)-1 ; $row++) {

					$field_table = explode('`', $line[$row] );

					if ( isset($field_table[1]) && $this->table != $field_table[1]
						&& ! in_array($field_table[1], $this->field) && ! strpos( $line[$row], "`fk_" )
						&& ! strpos( $line[$row], "ibfk" ) ){

						array_push($this->field, $field_table[1]);
					}

					if ( strpos( $line[$row], 'PRIMARY KEY' ) ) {
						$primary 			= explode('`', $line[$row]);
						$this->primary_key 	= $primary[1];
					}
				}
			}

		}

		$result = $this->_GenerateModel();

		if ( $result['successful'] == TRUE ) {
			$result_controller = $this->_GenerateController();
			if ( $result_controller['successful'] == TRUE  ) {
				$result['controller'] = $result_controller['controller'];

				$result_main_module = $this->_GenerateViewModuleHome();

				if ( $result_main_module['successful'] == TRUE  ) {
					$result['view_module'] = $result_main_module['module'];

					$result_table = $this->_GenerateViewTableList();

					if ( $result_table['successful'] == TRUE ) {
						$result['tbl'] = $result_table['tbl'];

						$result_js = $this->_GenerateJS();

						if ( $result_js['successful'] == TRUE ) {
							$result['js'] = $result_js['js'];

							$result_zip = $this->_GenerateZIP();

							if ( $result_zip['successful'] == TRUE ) {
								$result['zip'] = $result_zip['zip'];
							}
						}
					}
				}
			}
		}else{
			$result['successful'] 	= FALSE;
		}

		return $result;
	}

	private function _GenerateModel(){
		if ( ! is_dir('./components/'.$this->table) ) {
			mkdir('./components/'.$this->table, 0775);
		}

		if ( ! is_dir('./components/'.$this->table.'/models') ) {
			mkdir('./components/'.$this->table.'/models', 0775);
		}

		$route = './components/' . $this->table . '/models/' . ucfirst($this->table) . '_model.php';

		$model = fopen($route, "w+");

			fwrite($model,"<?php if (! defined('BASEPATH')) exit('No direct script access');\n");

			$this->_GenerateDocument('model', $model, ucwords($this->table));

			fwrite($model,"class ".ucwords($this->table)."_model extends CI_Model{\n");
			fwrite($model,"	\n");
			fwrite($model,"	public function __construct(){\n");
			fwrite($model,"		parent::__construct();\n");
			fwrite($model,"	}\n");
			fwrite($model,"\n");

			/**
			 * LISTADO PAGINADO
			 */
			fwrite($model,"	public function List".ucwords($this->table)."(\$rows = NULL, \$segment = NULL, \$list = NULL) {\n");
			$string_field = '';
			for ($i=0; $i < count($this->field); $i++) {
				if ( $i == count($this->field)-1 ) {
					$string_field .= $this->field[$i];
				}else{
					$string_field .= $this->field[$i].', ';
				}
			}
			fwrite($model,"		\$this->db->select('".$string_field."');\n");
			fwrite($model,"		//\$this->db->join('', '', '');\n");
			fwrite($model,"		//\$this->db->where('', );\n");
			fwrite($model,"	\n");
			fwrite($model,"		if( \$this->input->post('search') ){\n");
			fwrite($model,"			\$dato = \$this->db->escape_str(\$this->input->post('search'));\n");

			$string_search = '';
			for ($i=0; $i < count($this->field); $i++) {
				if ( $i == count($this->field)-1 ) {
					$string_search .= "					".$this->table.".".$this->field[$i]." LIKE \"%'.\$dato.'%\" ";
				}else{
					$string_search .= "					".$this->table.".".$this->field[$i]." LIKE \"%'.\$dato.'%\" OR \n";
				}
			}

			fwrite($model,"			\$this->db->where('( \n");
			fwrite($model,"".$string_search." \n");
			fwrite($model,"								)');\n");

			fwrite($model,"		}\n");
			fwrite($model,"	\n");
			fwrite($model,"		//\$this->db->order_by('', ''); // ASC || DESC\n");
			fwrite($model,"	\n");
			fwrite($model,"		if ( \$list ) {\n");
			fwrite($model,"			return \$this->db->get('".$this->table."', \$rows, ((\$segment > 0) ? \$segment:0))->result();\n");
			fwrite($model,"		}else{\n");
			fwrite($model,"			return \$this->db->from('".$this->table."')->get()->num_rows();\n");
			fwrite($model,"		}\n");
			fwrite($model,"	}\n");
			fwrite($model,"\n");

			/**
			 * METODO GUARDAR
			 */
			fwrite($model,"	public function Save".ucwords($this->table)."(){\n");
			foreach ($this->field as $key => $value) {
				if ( $value != $this->primary_key ) {
					fwrite($model,"		\$new['".$value."'] 	= \$this->input->post('".$value."');\n");
				}
			}
			fwrite($model,"		\n");
			fwrite($model,"		switch ( \$this->input->post('mode') ) {\n");
			fwrite($model,"			case '0': // SAVE\n");
			fwrite($model,"				if ( \$this->db->insert('".$this->table."', \$new) ) {\n");
			fwrite($model,"					return TRUE;\n");
			fwrite($model,"				}else{\n");
			fwrite($model,"					return FALSE;\n");
			fwrite($model,"				}\n");
			fwrite($model,"				break;\n");
			fwrite($model,"			\n");
			fwrite($model,"			case '1': // UPDATE\n");
			fwrite($model,"				\$this->db->where('".$this->primary_key."', \$this->input->post('".$this->primary_key."'));\n");
			fwrite($model,"				if ( \$this->db->update('".$this->table."', \$new) ) {\n");
			fwrite($model,"					return TRUE;\n");
			fwrite($model,"				}else{\n");
			fwrite($model,"					return FALSE;\n");
			fwrite($model,"				}\n");
			fwrite($model,"				break;\n");
			fwrite($model,"		}\n");
			fwrite($model,"	}\n");
			fwrite($model,"\n");

			/**
			 * METODO SELECCIONAR UN REGISTRO
			 */
			fwrite($model,"	public function Select".ucwords($this->table)."() {\n");
			$string_field = '';
			for ($i=0; $i < count($this->field); $i++) {
				if ( $i == count($this->field)-1 ) {
					$string_field .= $this->field[$i];
				}else{
					$string_field .= $this->field[$i].', ';
				}
			}
			fwrite($model,"		\$this->db->select('".$string_field."');\n");
			fwrite($model,"		\$this->db->from('".$this->table."');\n");
			fwrite($model,"		\$this->db->where('".$this->primary_key."', \$this->input->post('".$this->primary_key."'));\n");
			fwrite($model,"		//return \$this->db->get()->result();\n");
			fwrite($model,"		return \$this->db->get()->row();\n");
			fwrite($model,"	}\n");
			fwrite($model,"\n");
			/**
			 * ELIMINAR REGISTRO
			 */
			fwrite($model,"	public function Delete".ucwords($this->table)."(){\n");
			fwrite($model,"		return \$this->db->where('".$this->primary_key."', \$this->input->post('".$this->primary_key."'))->limit(1)->delete('".$this->table."');\n");
			fwrite($model,"	}\n");
			fwrite($model,"\n");
			fwrite($model,"}\n");
			fwrite($model,"/* End of file ".$this->table."_model.php */\n");
			fwrite($model,"/* Location: ./models/".$this->table."_model.php */");

		fclose($model);

		if ( file_exists($route) ) {
			$result['successful'] 	= TRUE;
			$result['model'] 		= $route;
		}else{
			$result['successful'] 	= FALSE;
		}

		return $result;
	}

	private function _GenerateController(){
		if ( ! is_dir('./components/'.$this->table.'/controllers') ) {
			mkdir('./components/'.$this->table.'/controllers', 0775);
		}

		$route = './components/' . $this->table . '/controllers/' . ucfirst($this->table) . '.php';

		$controller = fopen($route, "w+");

			fwrite($controller,"<?php if (! defined('BASEPATH')) exit('No direct script access');\n");

			$this->_GenerateDocument('controller', $controller, ucwords($this->table));

			fwrite($controller,"class ".ucwords($this->table)." extends CI_Controller { \n");
			fwrite($controller,"\n");
			fwrite($controller,"	var \$rows_pagination	= 20;\n");
			fwrite($controller,"	var \$number_links 		= 2;\n");
			fwrite($controller,"\n");
			fwrite($controller,"	public function __construct(){\n");
			fwrite($controller,"		parent::__construct();\n");
			fwrite($controller,"		\$this->load->library('pagination');\n");
			fwrite($controller,"		\$this->load->model('".$this->table."_model','model".ucwords($this->table)."');\n");
			fwrite($controller,"	}\n");
			fwrite($controller,"\n");
			fwrite($controller,"	public function list_".$this->table."(){\n");
			fwrite($controller,"		\$rows 				 = \$this->model".ucwords($this->table)."->List".ucwords($this->table)."(\$this->rows_pagination, \$this->uri->segment(3), FALSE);\n");
			fwrite($controller,"		\$information['list'] = \$this->model".ucwords($this->table)."->List".ucwords($this->table)."(\$this->rows_pagination, \$this->uri->segment(3), TRUE);\n");
			fwrite($controller,"		\$this->_paginacion(\$rows, '".$this->table."', 'pagination', 3);\n");
			fwrite($controller,"		\$parameters['table'] 	= \$this->load->view('".$this->table."/".$this->table."-table', \$information, TRUE);\n");
			fwrite($controller,"		\$modulo['template'] = \$this->load->view('".$this->table."/".$this->table."-module', \$parameters, TRUE);\n");
			fwrite($controller,"		\$this->load->view('template', \$modulo);\n");
			fwrite($controller,"	}\n");
			fwrite($controller,"	\n");
			fwrite($controller,"	public function pagination(){\n");
			fwrite($controller,"		if ( \$this->input->is_ajax_request() ) {\n");
			fwrite($controller,"			\$rows 				 = \$this->model".ucwords($this->table)."->List".ucwords($this->table)."(\$this->rows_pagination, \$this->uri->segment(3), FALSE);\n");
			fwrite($controller,"			\$information['list'] = \$this->model".ucwords($this->table)."->List".ucwords($this->table)."(\$this->rows_pagination, \$this->uri->segment(3), TRUE);\n");
			fwrite($controller,"			\$this->_paginacion(\$rows, '".$this->table."', 'pagination', 3);\n");
			fwrite($controller,"			\$this->load->view('".$this->table."/".$this->table."-table', \$information);\n");
			fwrite($controller,"		}\n");
			fwrite($controller,"	}\n");
			fwrite($controller,"	\n");
			fwrite($controller,"	public function save_".$this->table."(){\n");
			fwrite($controller,"		if ( \$this->input->is_ajax_request() ) {\n");
			fwrite($controller,"			\$this->load->library('form_validation');\n");
			fwrite($controller,"			if ( \$this->input->post('mode') == '1' ) {\n");
			fwrite($controller,"				\$this->form_validation->set_rules('".$this->primary_key."','".$this->primary_key."','required');\n");
			fwrite($controller,"			}\n");

			foreach ($this->field as $key => $value) {
				if ( $value != $this->primary_key ) {
					fwrite($controller,"			\$this->form_validation->set_rules('".$value."','".ucwords(str_replace('_', ' ',$value))."','required|max_length[255]');\n");
				}
			}

			fwrite($controller,"			if ( \$this->form_validation->run() === FALSE ){\n");
			fwrite($controller,"				\$result['message']		= \$this->form_validation->error_array();\n");
			fwrite($controller,"				\$result['successful']	= FALSE;\n");
			fwrite($controller,"			}else{\n");
			fwrite($controller,"				if ( \$this->model".ucwords($this->table)."->Save".ucwords($this->table)."() ) {\n");
			fwrite($controller,"					\$result['message']		= 'Saved Correctly';\n");
			fwrite($controller,"					\$result['successful']	= TRUE;\n");
			fwrite($controller,"				}else{\n");
			fwrite($controller,"					\$result['message']		= 'A problem occurred';\n");
			fwrite($controller,"					\$result['successful']	= FALSE;\n");
			fwrite($controller,"				}\n");
			fwrite($controller,"			}\n");
			fwrite($controller,"			echo json_encode(\$result);\n");
			fwrite($controller,"		}\n");
			fwrite($controller,"	}\n");
			fwrite($controller,"	\n");
			fwrite($controller,"	public function select_".$this->table."(){\n");
			fwrite($controller,"		if ( \$this->input->is_ajax_request() ) {\n");
			fwrite($controller,"			echo json_encode(\$this->model".ucwords($this->table)."->Select".ucwords($this->table)."());\n");
			fwrite($controller,"		}\n");
			fwrite($controller,"	}\n");
			fwrite($controller,"	\n");
			fwrite($controller,"	public function delete_".$this->table."(){\n");
			fwrite($controller,"		if ( \$this->input->is_ajax_request() ) {\n");
			fwrite($controller,"			if ( \$this->model".ucwords($this->table)."->Delete".ucwords($this->table)."() ) {\n");
			fwrite($controller,"				\$result['message']		= 'Properly Removed';\n");
			fwrite($controller,"				\$result['successful']	= TRUE;\n");
			fwrite($controller,"			}else{\n");
			fwrite($controller,"				\$result['message']		= 'A problem occurred';\n");
			fwrite($controller,"				\$result['successful']	= FALSE;\n");
			fwrite($controller,"			}\n");
			fwrite($controller,"			echo json_encode(\$result);\n");
			fwrite($controller,"		}\n");
			fwrite($controller,"	}\n");
			fwrite($controller,"	\n");
			fwrite($controller,"	private function _paginacion(\$total_records, \$controller, \$function, \$range_segment){\n");
			fwrite($controller,"		\$settings_pagination = array(\n");
			fwrite($controller,"			'base_url'				=> base_url().''.\$controller.'/'.\$function,\n");
			fwrite($controller,"			'total_rows'			=> \$total_records,\n");
			fwrite($controller,"			'per_page'				=> \$this->rows_pagination,\n");
			fwrite($controller,"			'num_links'				=> \$this->number_links,\n");
			fwrite($controller,"			'first_link'			=> '&laquo;',\n");
			fwrite($controller,"			'next_link'				=> '&rsaquo;',\n");
			fwrite($controller,"			'prev_link'				=> '&lsaquo;',\n");
			fwrite($controller,"			'last_link'				=> '&raquo;',\n");
			fwrite($controller,"			'uri_segment'			=> \$range_segment,\n");
			fwrite($controller,"			'full_tag_open'			=> '<div id=\"pagination\" class=\"pull-right\"><ul class=\"pagination pagination-sm\">',\n");
			fwrite($controller,"			'full_tag_close'		=> '</ul></div>',\n");
			fwrite($controller,"			'first_tag_open'		=> '<li>',\n");
			fwrite($controller,"			'first_tag_close'		=> '</li>',\n");
			fwrite($controller,"			'last_tag_open'			=> '<li>',\n");
			fwrite($controller,"			'last_tag_close'		=> '</li>',\n");
			fwrite($controller,"			'cur_tag_open'			=> '<li class=\"active\"><a>',\n");
			fwrite($controller,"			'cur_tag_close'			=> '</a></li>',\n");
			fwrite($controller,"			'next_tag_open'			=> '<li>',\n");
			fwrite($controller,"			'next_tag_close'		=> '</li>',\n");
			fwrite($controller,"			'prev_tag_open'			=> '<li>',\n");
			fwrite($controller,"			'prev_tag_close'		=> '</li>',\n");
			fwrite($controller,"			'num_tag_open'			=> '<li>',\n");
			fwrite($controller,"			'num_tag_close'			=> '</li>',\n");
			fwrite($controller,"			'page_query_string'		=> FALSE,\n");
			fwrite($controller,"			'query_string_segment'	=> 'per_page',\n");
			fwrite($controller,"			'display_pages'			=> TRUE,\n");
			fwrite($controller,"			'attributes'			=> array('class' => 'btn-pagination'));\n");
			fwrite($controller,"		\$this->pagination->initialize(\$settings_pagination);\n");
			fwrite($controller,"	}\n");
			fwrite($controller,"\n");
			fwrite($controller,"}\n");
			fwrite($controller,"/* End of file ".$this->table.".php */\n");
			fwrite($controller,"/* Location: ./controllers/".$this->table.".php */\n");

		fclose($controller);

		if ( file_exists($route) ) {
			$result['successful'] 	= TRUE;
			$result['controller']	= $route;
		}else{
			$result['successful'] 	= FALSE;
		}

		return $result;
	}

	private function _GenerateViewModuleHome(){
		if ( ! is_dir('./components/'.$this->table.'/views') ) {
			mkdir('./components/'.$this->table.'/views', 0775);
		}

		$route = './components/'.$this->table.'/views/'.$this->table.'-module.php';

		$module = fopen($route, "w+");

			fwrite($module,"<form id=\"form-$this->table\" name=\"form-$this->table\" class=\"form-horizontal well\" action=\"#\" method=\"POST\" onsubmit=\"return(false)\" >\n");
			fwrite($module,"	<div class=\"row\">\n");
			fwrite($module,"		<div class=\"col-sm-6\">\n");
			fwrite($module,"			<div class=\"input-group\">\n");
			fwrite($module,"				<input type=\"text\" id=\"search\" name=\"search\" autofocus=\"autofocus\" class=\"form-control\" placeholder=\"search\">\n");
			fwrite($module,"				<span class=\"input-group-btn\">\n");
			fwrite($module,"					<button id=\"btn-search\" id=\"btn-search\" class=\"btn btn-primary\" type=\"submit\"><span class=\"glyphicon glyphicon-search\"></span></button>\n");
			fwrite($module,"				</span>\n");
			fwrite($module,"			</div>\n");
			fwrite($module,"		</div>\n");
			fwrite($module,"		<div class=\"col-sm-6\">\n");
			fwrite($module,"			<button id=\"btn-new\" id=\"btn-new\" class=\"btn btn-success pull-right option\" title=\"New ".ucwords($this->table)."\" type=\"submit\"><span class=\"glyphicon glyphicon-plus\"></span></button>\n");
			fwrite($module,"		</div>\n");
			fwrite($module,"	</div>\n");
			fwrite($module,"</form>\n");
			fwrite($module,"\n");
			fwrite($module,"<div id=\"container-$this->table\">\n");
			fwrite($module,"	<?= \$table ?>\n");
			fwrite($module,"</div>\n");
			fwrite($module,"\n");
			fwrite($module,"<div id=\"modal-new-$this->table\" class=\"modal fade\">\n");
			fwrite($module,"	<div class=\"modal-dialog\">\n");
			fwrite($module,"		<div class=\"modal-content\">\n");
			fwrite($module,"			<div class=\"modal-header lead\">\n");
			fwrite($module,"				<button type=\"button\" class=\"close\" data-dismiss=\"modal\">×</button>\n");
			fwrite($module,"				<span id=\"lbl-title-modal\">ADD ".strtoupper($this->table)."</span>\n");
			fwrite($module,"			</div>\n");
			fwrite($module,"			<div class=\"modal-body\">\n");
			fwrite($module,"				<form id=\"form-new-$this->table\" name=\"form-new-$this->table\" class=\"form-horizontal\" action=\"#\" method=\"POST\" onsubmit=\"return(false)\" >\n");
			fwrite($module,"					<input type=\"hidden\" id=\"$this->primary_key\" name=\"$this->primary_key\">\n");
			fwrite($module,"					<input type=\"hidden\" id=\"mode\" name=\"mode\" value=\"0\">\n");
			fwrite($module,"					<div class=\"row\">\n");
			fwrite($module,"						<div class=\"col-sm-12\">\n");
			foreach ($this->field as $key => $value) {
				if ( $value != $this->primary_key ) {
					fwrite($module,"							<div class=\"form-group\">\n");
					fwrite($module,"								<label class=\"col-md-3 col-lg-3 control-label\" for=\"$value\">".ucwords(str_replace('_', ' ',$value))."</label>\n");
					fwrite($module,"								<div class=\"col-md-9 col-lg-8\">\n");
					fwrite($module,"									<input type=\"text\" id=\"$value\" name=\"$value\" class=\"form-control\" />\n");
					fwrite($module,"								</div>\n");
					fwrite($module,"							</div>\n");
				}
			}
			fwrite($module,"						</div>\n");
			fwrite($module,"					</div>\n");
			fwrite($module,"				</form>\n");
			fwrite($module,"			</div>\n");
			fwrite($module,"			<div class=\"modal-footer\">\n");
			fwrite($module,"				<button id=\"btn-new-$this->table\" name=\"btn-new-$this->table\" type=\"button\" class=\"btn btn-primary\" data-loading-text=\"Wait...\">Save</button>\n");
			fwrite($module,"			</div>\n");
			fwrite($module,"		</div>\n");
			fwrite($module,"	</div>\n");
			fwrite($module,"</div>\n");
			fwrite($module,"\n");
			fwrite($module,"<script src=\"js/".$this->table."-module.js\"></script>\n");

		fclose($module);

		if ( file_exists($route) ) {
			$result['successful'] 	= TRUE;
			$result['module']		= $route;
		}else{
			$result['successful'] 	= FALSE;
		}

		return $result;
	}

	private function _GenerateViewTableList(){
		if ( ! is_dir('./components/'.$this->table.'/views') ) {
			mkdir('./components/'.$this->table.'/views', 0775);
		}

		$route = './components/'.$this->table.'/views/'.$this->table.'-table.php';

		$tbl = fopen($route, "w+");

			fwrite($tbl,"<table class=\"table table-striped table-hover table-condensed\">\n");
			fwrite($tbl,"	<thead>\n");
			fwrite($tbl,"		<tr>\n");

			foreach ($this->field as $key => $value) {
				fwrite($tbl,"			<th>".strtoupper($value)."</th>\n");
			}
			fwrite($tbl,"			<th style=\"width:10%;\"></th>\n");

			fwrite($tbl,"		</tr>\n");
			fwrite($tbl,"	</thead>\n");
			fwrite($tbl,"	<tbody>\n");

			fwrite($tbl,"		<?php if ( \$list ): ?>\n");
			fwrite($tbl,"			<?php foreach (\$list as \$field): ?>\n");
				fwrite($tbl,"				<tr>\n");

				foreach ($this->field as $key => $value) {
					fwrite($tbl,"					<td><?= \$field->".$value." ?></td>\n");
				}

				fwrite($tbl,"					<td>\n");
					foreach ($this->field as $key => $value) {
						if ( $value == $this->primary_key ) {
							fwrite($tbl,"						<a data-".str_replace('_', '-', $this->primary_key)."=\"<?= \$field->".$value." ?>\" class=\"btn-delete btn btn-xs btn-danger pull-right option\" title=\"Delete\"><span class=\"glyphicon glyphicon-trash\" aria-hidden=\"true\"></span></a>\n");
							fwrite($tbl,"						<span class=\"pull-right\">&nbsp;</span>\n");
							fwrite($tbl,"						<a data-".str_replace('_', '-', $this->primary_key)."=\"<?= \$field->".$value." ?>\" class=\"btn-update btn btn-xs btn-info pull-right option\" title=\"Edit\"><span class=\"glyphicon glyphicon-pencil\" aria-hidden=\"true\"></span></a>\n");
						}
					}
				fwrite($tbl,"					</td>\n");
				fwrite($tbl,"				</tr>\n");
			fwrite($tbl,"			<?php endforeach ?>\n");
			fwrite($tbl,"		<?php else: ?>\n");
			fwrite($tbl,"			<tr>\n");
			fwrite($tbl,"				<td colspan=\"".(count($this->field) + 1)."\">No results found</td>\n");
			fwrite($tbl,"			</tr>\n");
			fwrite($tbl,"		<?php endif ?>\n");



			fwrite($tbl,"	</tbody>\n");
			fwrite($tbl,"</table>\n");
			fwrite($tbl,"\n");
			fwrite($tbl,"<?php echo \$this->pagination->create_links(); ?>\n");
			fwrite($tbl,"\n");
			fwrite($tbl,"<script type=\"text/javascript\">$(function() { $('.option').tooltip(); });</script>\n");

		fclose($tbl);

		if ( file_exists($route) ) {
			$result['successful'] 	= TRUE;
			$result['tbl']			= $route;
		}else{
			$result['successful'] 	= FALSE;
		}

		return $result;
	}

	private function _GenerateJS(){
		if ( ! is_dir('./components/'.$this->table.'/js') ) {
			mkdir('./components/'.$this->table.'/js', 0775);
		}

		$route = './components/'.$this->table.'/js/'.$this->table.'-module.js';

		$js = fopen($route, "w+");

			$this->_GenerateDocument('js', $js, $this->table);

			fwrite($js,"$(function() {\n");
			fwrite($js,"	\n");
			fwrite($js,"	/* SEARCH */\n");
			fwrite($js,"	$('#btn-search').click(function(){\n");
			fwrite($js,"		\$reload();\n");
			fwrite($js,"		return false;\n");
			fwrite($js,"	});\n");
			fwrite($js,"	\n");
			fwrite($js,"	/* MODAL */\n");
			fwrite($js,"	$('#btn-new').click(function(){\n");
			fwrite($js,"		$('#modal-new-$this->table').modal('show');\n");
			fwrite($js,"	});\n");
			fwrite($js,"	\n");
			fwrite($js,"	$('#modal-new-$this->table').on('hidden.bs.modal', function () {\n");
			fwrite($js,"		document.getElementById('form-new-$this->table').reset();\n");
			fwrite($js,"		$('#mode').val('0');\n");
			fwrite($js,"		$('#$this->primary_key').val('');\n");
			fwrite($js,"		$('#lbl-title-modal').html('ADD ".strtoupper($this->table)."');\n");
			fwrite($js,"		$('#form-new-$this->table div').removeClass('has-error');\n");
			fwrite($js,"	});\n");
			fwrite($js,"	\n");
			fwrite($js,"	/* SAVE */\n");
			fwrite($js,"	$('#btn-new-$this->table').click(function(){\n");
			fwrite($js,"		$('#btn-new-$this->table').button('loading');\n");
			fwrite($js,"		$.post(app.url + '".$this->table."/save_".$this->table."', $('#form-new-$this->table').serialize(), function(result){\n");
			fwrite($js,"			if( result.successful === true ){\n");
			fwrite($js,"				$('#btn-new-$this->table').button('reset');\n");
			fwrite($js,"				$.notificaciones('Notification', result.message, 'success');\n");
			fwrite($js,"				$('#modal-new-$this->table').modal('hide');\n");
			fwrite($js,"				\$reload();\n");
			fwrite($js,"			}else{\n");
			fwrite($js,"				$('#btn-new-$this->table').button('reset');\n");
			fwrite($js,"				first = true;\n");
			fwrite($js,"				$.each(result.message, function(field, notice){\n");
			fwrite($js,"					if (first){\n");
			fwrite($js,"						$('div .has-error').removeClass('has-error');\n");
			fwrite($js,"						error = new Object({ field: field, message: notice });\n");
			fwrite($js,"						first = false;\n");
			fwrite($js,"					}\n");
			fwrite($js,"					$('#'+field).parents('.form-group').addClass('has-error');\n");
			fwrite($js,"				});\n");
			fwrite($js,"				$.notificaciones('Incomplete Information', error.message, 'error');\n");
			fwrite($js,"				$('#'+error.field).focus();\n");
			fwrite($js,"			}\n");
			fwrite($js,"		},'json');\n");
			fwrite($js,"		return false;\n");
			fwrite($js,"	});\n");
			fwrite($js,"	\n");
			fwrite($js,"	/* EDIT */\n");
			fwrite($js,"	$('div#container-$this->table').on('click', 'a.btn-update', function(){\n");
			fwrite($js,"		$('#mode').val('1');\n");
			fwrite($js,"		$('#lbl-title-modal').html('EDITING ".strtoupper($this->table)."');\n");
			fwrite($js,"		var ".$this->primary_key." = $(this).attr('data-".str_replace('_', '-', $this->primary_key)."');\n");
			fwrite($js,"		$.post(app.url + '".$this->table."/select_".$this->table."', { ".$this->primary_key." : ".$this->primary_key." }, function(data){\n");

			foreach ($this->field as $key => $value) {
				fwrite($js,"			$('#$value').val(data.$value);\n");
			}
			fwrite($js,"			$('#modal-new-$this->table').modal('show');\n");
			fwrite($js,"		},'json');\n");
			fwrite($js,"		return false;\n");
			fwrite($js,"	});\n");
			fwrite($js,"	\n");
			fwrite($js,"	/* DELETE */\n");
			fwrite($js,"	$('div#container-$this->table').on('click', 'a.btn-delete', function(){\n");
			fwrite($js,"		var ".$this->primary_key." = $(this).attr('data-".str_replace('_', '-', $this->primary_key)."');\n");
			fwrite($js,"		$.confirmar('Are you sure you want to delete?',{ \n");
			fwrite($js,"			aceptar: function(){\n");
			fwrite($js,"				$.post(app.url + '".$this->table."/delete_".$this->table."', { ".$this->primary_key." : ".$this->primary_key." }, function(result){\n");
			fwrite($js,"					if ( result.successful === true ){\n");
			fwrite($js,"						$.notificaciones('Notification', result.message, 'success');\n");
			fwrite($js,"						\$reload();\n");
			fwrite($js,"					}else{\n");
			fwrite($js,"						if ( result.successful === false ) {\n");
			fwrite($js,"							$.notificaciones('Error', result.message, false);\n");
			fwrite($js,"						};\n");
			fwrite($js,"					};\n");
			fwrite($js,"				},'json');\n");
			fwrite($js,"			}\n");
			fwrite($js,"		});\n");
			fwrite($js,"		return false;\n");
			fwrite($js,"	});\n");
			fwrite($js,"	\n");
			fwrite($js,"	/* PAGINATION */\n");
			fwrite($js,"	$('div#container-$this->table').on('click', 'div#pagination ul li a.btn-pagination',function(){\n");
			fwrite($js,"		var parameters = ($(this).attr('href'));\n");
			fwrite($js,"		$.post(parameters, $('#form-$this->table').serialize(), function(data){\n");
			fwrite($js,"			$('#container-$this->table').html(data);\n");
			fwrite($js,"		});\n");
			fwrite($js,"		return false;\n");
			fwrite($js,"	});\n");
			fwrite($js,"	\n");
			fwrite($js,"	/* GRAL FUNCTION LISTING TO RECHARGE */\n");
			fwrite($js,"	\$reload = (function () {\n");
			fwrite($js,"		$.post(app.url + '".$this->table."/pagination', $('#form-$this->table').serialize(), function(result){\n");
			fwrite($js,"			$('#container-$this->table').html(result);\n");
			fwrite($js,"		});\n");
			fwrite($js,"	});\n");
			fwrite($js,"	\n");
			fwrite($js,"});");

		fclose($js);

		if ( file_exists($route) ) {
			$result['successful'] 	= TRUE;
			$result['js']			= $route;
		}else{
			$result['successful'] 	= FALSE;
		}

		return $result;
	}

	private function _GenerateZIP(){
		$zip 		= new ZipArchive();
		$file_zip 	= './components/'.$this->table.'.zip';

		if( $zip->open($file_zip, ZIPARCHIVE::CREATE) === true ) {

			$zip->addFile('./components/'.$this->table.'/controllers/'.$this->table.'.php', '/'.$this->table.'/controllers/'.ucfirst($this->table).'.php');
			$zip->addFile('./components/'.$this->table.'/models/'.$this->table.'_model.php', '/'.$this->table.'/models/'.ucfirst($this->table).'_model.php');
			$zip->addFile('./components/'.$this->table.'/js/'.$this->table.'-module.js', '/'.$this->table.'/js/'.$this->table.'-module.js');
			$zip->addFile('./components/'.$this->table.'/views/'.$this->table.'-module.php', '/'.$this->table.'/views/'.$this->table.'-module.php');
			$zip->addFile('./components/'.$this->table.'/views/'.$this->table.'-table.php', '/'.$this->table.'/views/'.$this->table.'-table.php');
			$zip->close();

			$result['zip']			= $file_zip;
			$result['successful']	= TRUE;
		}else{
			$result['successful']	= FALSE;
		}

		return $result;
	}

	private function _GenerateDocument($type, $type_component, $subpackage){
		fwrite($type_component,"/** \n");
		fwrite($type_component," * @package { NameApp }\n");

		switch ($type) {
			case 'model':
					fwrite($type_component," * @subpackage ".$subpackage."_model\n");
					fwrite($type_component," * @version 1.0\n");
				break;

			case 'controller':
					fwrite($type_component," * @subpackage ".$subpackage."\n");
					fwrite($type_component," * @version 1.0\n");
				break;

			case 'js':
					fwrite($type_component," * @subpackage ".$subpackage."-module.js v1.0\n");
				break;
		}

		fwrite($type_component," * @author { NameDev } || { EmailDev } || { TwitterDev }\n");
		fwrite($type_component," */ \n");
	}

	public function ReadComponent($route){
		$code = NULL;
		$sql 	= fopen($route , "r");
		while(!feof($sql)) {
			$code .= str_replace(
									array('<','>'),
									array('&lt;','&gt;'),
									fgets($sql));
		}
		fclose($sql);

		return $code;
	}

}
/* End of file YeeGenerator.php */
/* Location: ./libraries/YeeGenerator.php */
