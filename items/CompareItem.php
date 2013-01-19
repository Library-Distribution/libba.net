<?php
class CompareItem
{
	private $id;
	private $handle;
	private $zip;
	private $doc;
	private $xp;

	function __construct($api, $id)
	{
		$this->id = $id;
		try {
			$item = $api->loadItem($id);
		} catch (HttpException $e) {
			# TODO: error handling
			die('failed');
		}

		$this->handle = tmpfile();
		fputs($this->handle, $item);

		$data = stream_get_meta_data($this->handle);
		$path = $data['uri'];

		$this->zip = new ZipArchive();
		$this->zip->open($path);

		$this->doc = new DOMDocument();
		$this->doc->loadXML($this->zip->getFromName('definition.ald'));

		$this->xp = new DOMXPath($this->doc);
		$this->xp->registerNamespace('ald', 'ald://package/schema/2012');
	}

	function __destruct()
	{
		$this->zip->close();
		fclose($this->handle);
	}

	public function files()
	{
		$files = array('doc' => array(), 'src' => array(), 'logo' => array(), 'schema' => array('definition.ald'));

		foreach ($this->xp->query('/*/ald:files/ald:src/ald:file/@ald:path') AS $file)
			$files['src'][] = $file->nodeValue;

		foreach ($this->xp->query('/*/ald:files/ald:doc/ald:file/@ald:path') AS $file)
			$files['doc'][] = $file->nodeValue;

		foreach ($this->xp->query('/*/@ald:logo-image') AS $file)
			$files['logo'][] = $file->nodeValue;

		return $files;
	}

	public function hasFile($name)
	{
		return $this->zip->locateName($name) !== FALSE;
	}

	public function getFile($name)
	{
		return $this->zip->getFromName($name);
	}
}
?>