<?php
defined( '_JEXEC' ) or die;
jimport( 'joomla.plugin.plugin' );

class plgSearchEventos extends JPlugin{	
	
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	function onContentSearchAreas()
	{
		static $areas = array(
			'eventos' => 'Evento'
		);
		return $areas;
	}

	function onContentSearch( $text, $phrase='', $ordering='', $areas=null ){
		
		$db 	= JFactory::getDBO();
		$user	= JFactory::getUser(); 
		$groups	= implode(',', $user->getAuthorisedViewLevels());		
 
		if (is_array( $areas )) {
			if (!array_intersect( $areas, array_keys( $this->onContentSearchAreas() ) )) {
				return array();
			}
		}
 
		$text = trim( $text );

		if ($text == '') {
			return array();
		}

		$wheres = array();
		switch ($phrase) {
 
			// Search exact
			case 'exact':
				$text		= $db->Quote( '%'.$db->escape( $text, true ).'%', false );
				$wheres2 	= array();
				$wheres2[] 	= 'LOWER(p.evento) LIKE '.$text;
				$where 		= '(' . implode( ') OR (', $wheres2 ) . ')';
				break;
 
			// Search all or any
			case 'all':
			case 'any':
 
			// Set default
			default:
				$words 	= explode( ' ', $text );
				$wheres = array();
				foreach ($words as $word)
				{
					$word		= $db->Quote( '%'.$db->escape( $word, true ).'%', false );
					$wheres2 	= array();
					$wheres2[] 	= 'LOWER(p.evento) LIKE '.$word;
					$wheres[] 	= implode( ' OR ', $wheres2 );
				}
				$where = '((' . implode( ($phrase == 'all' ? ') AND (' : ') OR ('), $wheres ) . '))';
				break;
		}
 
		// Ordering of the results
		switch ( $ordering ) {
 
			//Alphabetic, ascending
			case 'alpha':
				$order = 'p.evento ASC';
				break;
 
			// Oldest first
			case 'oldest':
				$order = 'p.created ASC';
				break;		
 
			// Newest first
			case 'newest':
				$order = 'p.created DESC';
				break;
				
			// Popular first
			case 'popular':
 
			// Default setting: alphabetic, ascending
			default:
				$order = 'p.evento ASC, p.created DESC';
				break;
		}
	
		$db = JFactory::getDBO();	
		
		$db->setQuery("SELECT p.evento AS evento, p.created AS created, p.publicado AS publicado, p.descricao AS descricao, p.inicio AS inicio, p.id AS idp
							FROM #__chronoengine_chronoforms_datatable_evento AS p
							WHERE $where
								AND p.publicado = 1
							ORDER BY $order");
		
		$rows = null;
		$rows = $db->loadObjectList();
		
		foreach($rows as $key => $row) {
			$rows[$key]->title		= 'Evento: '.$row->evento;
			$rows[$key]->text		= $row->descricao;
			$rows[$key]->section		= 'Eventos';
			$rows[$key]->created		= '';
			$rows[$key]->href		= 'index.php?option=com_chronoforms5&chronoform=ver-evento&id='.$row->idp;
		}
		
		//Return the search results in an array
		return $rows;
	}
}