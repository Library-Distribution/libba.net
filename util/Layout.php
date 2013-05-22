<?php
require_once dirname(__FILE__) . '/rewriter.php';

class Layout {
	private $scripts;
	private $styles;
	private $template;
	private $content;

	const THEME = 'default';

	public function __construct($file) {
		$full_path = dirname(__FILE__) . '/../layouts/' . $file . '.ini';
		if (!file_exists($full_path)) {
			throw new Exception('Layout file does not exist');
		}
		$data = parse_ini_file($full_path, true);

		# here we can realize themes:
		# * allow other sections in INI with theme names
		# * use the section with the name of the current theme

		$this->scripts = isset($data[self::THEME]['scripts']) ? (array)$data[self::THEME]['scripts'] : array();
		$this->styles = isset($data[self::THEME]['styles']) ? (array)$data[self::THEME]['styles'] : array();
		$this->sanitize();

		if (isset($data[self::THEME]['template'])) {
			$full_path = dirname(__FILE__) . '/../templates/' . $data[self::THEME]['template'] . '.php';
			if (!file_exists($full_path)) {
				throw new Exception('Template file does not exist');
			}
			$this->template = $full_path;
		} else {
			throw new Exception('Layout must specify template!');
		}
	}

	public function sanitize() {
		foreach ($this->scripts AS &$script) {
			if (substr($script, 0, 2) != '//') {
				$script = 'javascript/' . $script;
			}
			if (substr($script, -3, 3) != '.js') {
				$script .= '.js';
			}
		}
		foreach ($this->styles AS &$style) {
			if (substr($style, 0, 2) != '//') {
				$style = 'style/' . $style;
			}
			if (substr($style, -4, 4) != '.css') {
				$style .= '.css';
			}
		}
	}

	public function evaluate($template) {
		ob_start(); # swallow all output

		# merge in layout data
		$template['scripts'] = $this->scripts;
		$template['styles'] = $this->styles;

		require $this->template; # process the template

		$this->content = ob_get_contents(); # save the output
		ob_end_clean(); # ... and clean the output buffer
	}

	public function rewrite() {
		$this->content = rewrite($this->content);
	}

	public function output() {
		echo $this->content;
	}
}

define('SCRIPT_JQUERY_EXT', '//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js');
define('SCRIPT_JQUERY_UI_EXT', '//ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js');
?>