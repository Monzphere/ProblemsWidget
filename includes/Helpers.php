<?php declare(strict_types = 0);

namespace Modules\ProblemsBySvMnz\Includes;

class Helpers {
	public static function getSystemStatusByTags(array $data): array {
		error_log('DEBUG - Iniciando getSystemStatusByTags');
		error_log('DEBUG - Estrutura completa de data: ' . print_r($data, true));
		error_log('DEBUG - Filter completo: ' . print_r(isset($data['filter']) ? $data['filter'] : [], true));
		error_log('DEBUG - Filter tags: ' . print_r(isset($data['filter']['tags']) ? $data['filter']['tags'] : [], true));
		
		$tags = [];
		$problems_by_tags = [];
		$filter_tags = isset($data['filter']['tags']) ? $data['filter']['tags'] : [];
		$evaltype = isset($data['filter']['evaltype']) ? $data['filter']['evaltype'] : TAG_EVAL_TYPE_AND_OR;

		error_log('DEBUG - Filter tags após atribuição: ' . print_r($filter_tags, true));
		error_log('DEBUG - Eval type: ' . $evaltype);

		// Se temos filtros, vamos criar apenas as tags que queremos mostrar
		if (!empty($filter_tags)) {
			error_log('DEBUG - Processando com filtros de tag');
			$filtered_problems = [];
			
			// Primeiro coletamos todos os problemas que correspondem aos filtros
			if (isset($data['groups'])) {
				foreach ($data['groups'] as $group) {
					if (isset($group['stats'])) {
						foreach ($group['stats'] as $severity => $stats) {
							if (isset($stats['problems'])) {
								foreach ($stats['problems'] as $problem) {
									if (!isset($problem['tags']) || !is_array($problem['tags'])) {
										continue;
									}

									error_log('DEBUG - Processando problema: ' . print_r($problem, true));
									$matches = 0;
									$matched_tags = [];
									
									// Verifica cada tag do filtro
									foreach ($filter_tags as $filter_tag) {
										foreach ($problem['tags'] as $tag) {
											if ($tag['tag'] === $filter_tag['tag'] && 
												($filter_tag['value'] === '' || $tag['value'] === $filter_tag['value'])) {
												$matches++;
												$matched_tags[] = [
													'tag' => $filter_tag['tag'],
													'value' => $filter_tag['value']
												];
												break;
											}
										}
									}

									// Decide se inclui o problema baseado no tipo de avaliação
									$include_problem = false;
									if ($evaltype == TAG_EVAL_TYPE_AND_OR && $matches == count($filter_tags)) {
										$include_problem = true;
									}
									elseif ($evaltype == TAG_EVAL_TYPE_OR && $matches > 0) {
										$include_problem = true;
									}

									if ($include_problem) {
										$problem['severity'] = (int) $severity;
										// Importante: substitui as tags do problema apenas pelas tags que correspondem ao filtro
										$problem['tags'] = $matched_tags;
										$filtered_problems[] = $problem;
									}
								}
							}
						}
					}
				}
			}

			error_log('DEBUG - Número de problemas filtrados: ' . count($filtered_problems));

			// Agora processamos apenas as tags que queremos mostrar
			foreach ($filter_tags as $filter_tag) {
				$tag_key = $filter_tag['tag'] . ':' . $filter_tag['value'];
				$tags[$tag_key] = [
					'tag' => $filter_tag['tag'],
					'value' => $filter_tag['value'],
					'problems' => [],
					'severities' => array_fill(0, TRIGGER_SEVERITY_COUNT, 0)
				];

				// Adiciona os problemas que têm esta tag
				foreach ($filtered_problems as $problem) {
					foreach ($problem['tags'] as $tag) {
						if ($tag['tag'] === $filter_tag['tag'] && 
							($filter_tag['value'] === '' || $tag['value'] === $filter_tag['value'])) {
							if (!in_array($problem['eventid'], array_column($tags[$tag_key]['problems'], 'eventid'))) {
								$tags[$tag_key]['problems'][] = $problem;
								$tags[$tag_key]['severities'][$problem['severity']]++;
							}
							break;
						}
					}
				}
			}
		}
		// Se não temos filtros, mostra todas as tags
		else {
			error_log('DEBUG - Processando sem filtros de tag');
			if (isset($data['groups'])) {
				foreach ($data['groups'] as $group) {
					if (isset($group['stats'])) {
						foreach ($group['stats'] as $severity => $stats) {
							if (isset($stats['problems'])) {
								foreach ($stats['problems'] as $problem) {
									if (!isset($problem['tags']) || !is_array($problem['tags'])) {
										continue;
									}

									$problem['severity'] = (int) $severity;
									
									foreach ($problem['tags'] as $tag) {
										if (!isset($tag['tag']) || !isset($tag['value'])) {
											continue;
										}

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

		error_log('DEBUG - Tags finais geradas: ' . print_r(array_column($problems_by_tags, 'name'), true));
		return $problems_by_tags;
	}
}
