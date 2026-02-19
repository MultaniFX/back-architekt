<?php
declare(strict_types=1);

class BrotarchitektData {

	public static function get_flours(): array {
		return array(
			'wheat' => array(
				'name'       => Lang::get('flour_wheat'),
				'types'      => array('550', '812', '1050', '1600', Lang::get('flour_type_vollkorn')),
				'category'   => 'standard',
				'level_main' => 1,
				'level_side' => 1,
			),
			'rye' => array(
				'name'       => Lang::get('flour_rye'),
				'types'      => array('997', '1150', '1370', '1740', Lang::get('flour_type_vollkorn')),
				'category'   => 'standard',
				'level_main' => 1,
				'level_side' => 1,
			),
			'spelt' => array(
				'name'       => Lang::get('flour_spelt'),
				'types'      => array('630', '812', '1050', Lang::get('flour_type_vollkorn')),
				'category'   => 'standard',
				'level_main' => 1,
				'level_side' => 1,
			),
			'semola' => array(
				'name'       => Lang::get('flour_semola'),
				'types'      => array(Lang::get('flour_type_hartweizen')),
				'category'   => 'standard',
				'level_main' => 1,
				'level_side' => 1,
			),
			'emmer' => array(
				'name'       => Lang::get('flour_emmer'),
				'types'      => array('812', Lang::get('flour_type_vollkorn')),
				'category'   => 'ancient',
				'level_main' => 4,
				'level_side' => 3,
			),
			'einkorn' => array(
				'name'       => Lang::get('flour_einkorn'),
				'types'      => array('812', Lang::get('flour_type_vollkorn')),
				'category'   => 'ancient',
				'level_main' => 4,
				'level_side' => 3,
			),
			'kamut' => array(
				'name'       => Lang::get('flour_kamut'),
				'types'      => array(Lang::get('flour_type_vollkorn')),
				'category'   => 'ancient',
				'level_main' => 4,
				'level_side' => 3,
			),
		);
	}

	public static function get_extras(): array {
		return array(
			'sunflower' => array('name' => Lang::get('extra_sunflower'), 'ratio' => 0.75, 'ta_raise' => false, 'category' => 'kern'),
			'pumpkin'   => array('name' => Lang::get('extra_pumpkin'), 'ratio' => 0.75, 'ta_raise' => false, 'category' => 'kern'),
			'sesame'    => array('name' => Lang::get('extra_sesame'), 'ratio' => 0.75, 'ta_raise' => false, 'category' => 'kern'),
			'linseed'   => array('name' => Lang::get('extra_linseed'), 'ratio' => 4, 'ta_raise' => true, 'category' => 'ta_raise'),
			'oatmeal'   => array('name' => Lang::get('extra_oatmeal'), 'ratio' => 4, 'ta_raise' => true, 'category' => 'ta_raise'),
			'old_bread' => array('name' => Lang::get('extra_old_bread'), 'ratio' => 4, 'ta_raise' => true, 'category' => 'ta_raise'),
			'grist'     => array('name' => Lang::get('extra_grist'), 'ratio' => 4, 'ta_raise' => true, 'category' => 'ta_raise'),
		);
	}

	public static function get_level_info(): array {
		return array(
			1 => array('label' => Lang::get('level_1'), 'main_flours' => 1, 'side_flours' => 0, 'ancient_main' => false, 'ancient_side' => false, 'ta_base' => 168, 'ta_max' => 173, 'max_extras' => 1, 'recommended_back' => 'pot'),
			2 => array('label' => Lang::get('level_2'), 'main_flours' => 1, 'side_flours' => 1, 'ancient_main' => false, 'ancient_side' => false, 'ta_base' => 170, 'ta_max' => 175, 'max_extras' => 2, 'recommended_back' => 'pot'),
			3 => array('label' => Lang::get('level_3'), 'main_flours' => 1, 'side_flours' => 2, 'ancient_main' => false, 'ancient_side' => true, 'ta_base' => 173, 'ta_max' => 180, 'max_extras' => 3, 'recommended_back' => null),
			4 => array('label' => Lang::get('level_4'), 'main_flours' => 2, 'side_flours' => 3, 'ancient_main' => true, 'ancient_side' => true, 'ta_base' => 176, 'ta_max' => 185, 'max_extras' => 5, 'recommended_back' => 'stone'),
			5 => array('label' => Lang::get('level_5'), 'main_flours' => 3, 'side_flours' => 3, 'ancient_main' => true, 'ancient_side' => true, 'ta_base' => 180, 'ta_max' => 190, 'max_extras' => 5, 'recommended_back' => 'steel'),
		);
	}

	public static function get_sourdough_types(): array {
		return array(
			'rye'           => array('name' => Lang::get('sourdough_rye'), 'ta' => 200, 'flour_grain' => 'rye'),
			'wheat'         => array('name' => Lang::get('sourdough_wheat'), 'ta' => 200, 'flour_grain' => 'wheat'),
			'spelt'         => array('name' => Lang::get('sourdough_spelt'), 'ta' => 200, 'flour_grain' => 'spelt'),
			'lievito_madre' => array('name' => Lang::get('sourdough_lievito'), 'ta' => 150, 'flour_grain' => 'wheat'),
		);
	}

	public static function get_flours_for_js(): array {
		$flours = self::get_flours();
		$out = array();
		foreach ($flours as $grain_key => $data) {
			foreach ($data['types'] as $type) {
				$out[] = array(
					'id'         => $grain_key . '_' . $type,
					'grain'      => $grain_key,
					'type'       => $type,
					'label'      => $data['name'] . ' ' . $type,
					'category'   => $data['category'],
					'level_main' => $data['level_main'],
					'level_side' => $data['level_side'],
				);
			}
		}
		return $out;
	}

	public static function get_extras_for_js(): array {
		$extras = self::get_extras();
		$out = array();
		foreach ($extras as $key => $data) {
			$out[] = array(
				'id'       => $key,
				'name'     => $data['name'],
				'ratio'    => $data['ratio'],
				'ta_raise' => $data['ta_raise'],
				'category' => $data['category'],
			);
		}
		return $out;
	}

	public static function get_level_info_for_js(): array {
		return self::get_level_info();
	}

	public static function get_flour_label(?string $id): string {
		if ($id === null || $id === '') return '';
		foreach (self::get_flours_for_js() as $f) {
			if ($f['id'] === $id) return $f['label'];
		}
		return $id;
	}
}
