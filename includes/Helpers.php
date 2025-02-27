<?php declare(strict_types = 0);

namespace Modules\ProblemsBySvMnz\Includes;

class Helpers {
	public static function getSystemStatusByTags(array $data): array {

		$tags = [];
		$problems_by_tags = [];
		$filter_tags = isset($data['filter']['tags']) ? $data['filter']['tags'] : [];
		$evaltype = isset($data['filter']['evaltype']) ? $data['filter']['evaltype'] : TAG_EVAL_TYPE_AND_OR;

		// Primeiro, coleta todos os problemas e suas tags
		$all_problems = [];
		if (isset($data['problems'])) {
			foreach ($data['problems'] as $problem) {
				if (!isset($problem['tags']) || !is_array($problem['tags'])) {
					continue;
				}
				$all_problems[] = $problem;
			}
		}

		// Se não encontrou problemas diretamente, procura nos grupos
		if (empty($all_problems) && isset($data['groups'])) {
			foreach ($data['groups'] as $group) {
				if (isset($group['stats'])) {
					foreach ($group['stats'] as $severity => $stats) {
						if (isset($stats['problems']) && is_array($stats['problems'])) {
							foreach ($stats['problems'] as $problem) {
								if (!isset($problem['tags']) || !is_array($problem['tags'])) {
									continue;
								}
								$problem['severity'] = (int) $severity;
								$all_problems[] = $problem;
							}
						}
					}
				}
			}
		}


		// Separa os filtros em tags para excluir e tags para incluir
		$exclude_tags = [];
		$include_tags = [];
		foreach ($filter_tags as $filter_tag) {
			if (isset($filter_tag['operator']) && ($filter_tag['operator'] == 8 || $filter_tag['operator'] == 5)) {
				$exclude_tags[] = $filter_tag['tag'];
			} else {
				$include_tags[] = $filter_tag;
			}
		}



		// Processa cada problema
		foreach ($all_problems as $problem) {
			$filtered_tags = [];
			$include_problem = true;

			// Se temos tags para incluir, verifica se o problema atende aos critérios
			if (!empty($include_tags)) {
				$matches = 0;
				foreach ($include_tags as $filter_tag) {
					foreach ($problem['tags'] as $tag) {
						if ($tag['tag'] === $filter_tag['tag'] && 
							($filter_tag['value'] === '' || $tag['value'] === $filter_tag['value'])) {
							$matches++;
							$filtered_tags[] = $tag;
							break;
						}
					}
				}

				if ($evaltype == TAG_EVAL_TYPE_AND_OR && $matches != count($include_tags)) {
					$include_problem = false;
				}
				elseif ($evaltype == TAG_EVAL_TYPE_OR && $matches == 0) {
					$include_problem = false;
				}
			}

			// Se o problema deve ser incluído, adiciona todas as tags não excluídas
			if ($include_problem) {
				foreach ($problem['tags'] as $tag) {
					if (!in_array($tag['tag'], $exclude_tags) && 
						(!in_array($tag, $filtered_tags, true))) {
						$filtered_tags[] = $tag;
					}
				}

				if (!empty($filtered_tags)) {
					$problem['tags'] = array_unique($filtered_tags, SORT_REGULAR);
					
					// Agrupa por tag
					foreach ($problem['tags'] as $tag) {
						$tag_key = $tag['tag'].':'.$tag['value'];
						
						if (!isset($tags[$tag_key])) {
							$tags[$tag_key] = [
								'tag' => $tag['tag'],
								'value' => $tag['value'],
								'problems' => [],
								'severities' => array_fill(0, TRIGGER_SEVERITY_COUNT, 0)
							];
						}

						if (!in_array($problem['eventid'], array_column($tags[$tag_key]['problems'], 'eventid'))) {
							$tags[$tag_key]['problems'][] = $problem;
							$tags[$tag_key]['severities'][$problem['severity']]++;
						}
					}
				}
			}
		}

		// Cria o array final apenas com as tags que têm problemas
		foreach ($tags as $tag_key => $tag_data) {
			if (count($tag_data['problems']) > 0) {
				$problems_by_tags[] = [
					'groupid' => md5($tag_key),
					'name' => $tag_data['tag'].': '.$tag_data['value'],
					'problems' => $tag_data['problems'],
					'stats' => [
						'severities' => $tag_data['severities'],
						'problems' => $tag_data['problems']
					],
					'has_problems' => true
				];
			}
		}

		usort($problems_by_tags, function($a, $b) {
			return strcmp($a['name'], $b['name']);
		});


		return $problems_by_tags;
	}

	private static function matchesOperator($tag, $filter_tag) {
		// Se as tags não correspondem, retorna false imediatamente
		if ($tag['tag'] !== $filter_tag['tag']) {
			return false;
		}

		// Se não há operador definido ou valor do filtro está vazio, considera match
		if (!isset($filter_tag['operator']) || $filter_tag['value'] === '') {
			return true;
		}

		switch ($filter_tag['operator']) {
			case 0: // Equals
				return $tag['value'] === $filter_tag['value'];
			case 1: // Does not equal
				return $tag['value'] !== $filter_tag['value'];
			case 2: // Contains
				return stripos($tag['value'], $filter_tag['value']) !== false;
			case 3: // Does not contain
				return stripos($tag['value'], $filter_tag['value']) === false;
			case 4: // Exists
				return true;
			case 6: // Matches regex
				return @preg_match('/'.$filter_tag['value'].'/', $tag['value']);
			case 7: // Does not match regex
				return !@preg_match('/'.$filter_tag['value'].'/', $tag['value']);
			default:
				return false;
		}
	}
} 
