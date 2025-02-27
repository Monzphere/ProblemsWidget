<?php declare(strict_types = 0);
/*
** Copyright (C) 2001-2025 Zabbix SIA
**
** This program is free software: you can redistribute it and/or modify it under the terms of
** the GNU Affero General Public License as published by the Free Software Foundation, version 3.
**
** This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
** without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
** See the GNU Affero General Public License for more details.
**
** You should have received a copy of the GNU Affero General Public License along with this program.
** If not, see <https://www.gnu.org/licenses/>.
**/


namespace Modules\ProblemsBySvMnz\Actions;

use APP,
	CControllerDashboardWidgetView,
	CControllerResponseData;

use Modules\ProblemsBySvMnz\Widget;
use Modules\ProblemsBySvMnz\Includes\Helpers;

require_once APP::getRootDir().'/include/blocks.inc.php';

class WidgetView extends CControllerDashboardWidgetView {

	protected function init(): void {
		parent::init();

		$this->addValidationRules([
			'initial_load' => 'in 0,1'
		]);
	}

	protected function doAction(): void {
		// Editing template dashboard?
		if ($this->isTemplateDashboard() && !$this->fields_values['override_hostid']) {
			$this->setResponse(new CControllerResponseData([
				'name' => $this->getInput('name', $this->widget->getDefaultName()),
				'error' => _('No data.'),
				'user' => [
					'debug_mode' => $this->getDebugMode()
				]
			]));
		}
		else {
			$filter = [
				'groupids' => !$this->isTemplateDashboard() ? getSubGroups($this->fields_values['groupids']) : null,
				'exclude_groupids' => !$this->isTemplateDashboard()
					? getSubGroups($this->fields_values['exclude_groupids'])
					: null,
				'hostids' => !$this->isTemplateDashboard()
					? $this->fields_values['hostids']
					: $this->fields_values['override_hostid'],
				'problem' => $this->fields_values['problem'],
				'severities' => $this->fields_values['severities'],
				'show_type' => !$this->isTemplateDashboard() ? $this->fields_values['show_type'] : Widget::SHOW_TOTALS,
				'layout' => $this->fields_values['layout'],
				'show_suppressed' => $this->fields_values['show_suppressed'],
				'hide_empty_groups' => !$this->isTemplateDashboard() ? $this->fields_values['hide_empty_groups'] : null,
				'show_opdata' => $this->fields_values['show_opdata'],
				'ext_ack' => $this->fields_values['ext_ack'],
				'show_timeline' => $this->fields_values['show_timeline'],
				'evaltype' => $this->fields_values['evaltype'],
				'tags' => [],
				'tag_priority' => $this->fields_values['tag_priority']
			];

			// Obtém todos os dados sem filtrar por tags
			$data = getSystemStatusData($filter);
			
			// Inicializa o array de problemas se não existir
			if (!isset($data['problems'])) {
				$data['problems'] = [];
			}

			if ($filter['show_type'] == Widget::SHOW_TOTALS) {
				$data['groups'] = getSystemStatusTotals($data);
			}
			elseif ($filter['show_type'] == Widget::SHOW_TAGS) {
				if (!isset($data['groups']) || !is_array($data['groups'])) {
					$data['groups'] = [];
				}

				// Adiciona os filtros de tag apenas para nossa função
				$data['filter'] = [
					'evaltype' => $this->fields_values['evaltype'],
					'tags' => $this->fields_values['tags']
				];
				
				$tag_groups = Helpers::getSystemStatusByTags($data);
				
				// Ordenar grupos por prioridade de tag se definido
				if (!empty($filter['tag_priority'])) {
					$priorities = array_map('trim', explode(',', $filter['tag_priority']));
					
					usort($tag_groups, function($a, $b) use ($priorities) {
						$tag_a = explode(':', $a['name'])[0];
						$tag_b = explode(':', $b['name'])[0];
						
						$pos_a = array_search($tag_a, $priorities);
						$pos_b = array_search($tag_b, $priorities);
						
						if ($pos_a !== false && $pos_b !== false) {
							return $pos_a - $pos_b;
						}
						elseif ($pos_a !== false) {
							return -1;
						}
						elseif ($pos_b !== false) {
							return 1;
						}
						
						return strcmp($a['name'], $b['name']);
					});
				}
				
				foreach ($tag_groups as &$group) {
					$group['groupid'] = md5($group['name']); 
					$group['has_problems'] = !empty($group['problems']);
					
					if (!isset($group['stats'])) {
						$group['stats'] = [];
					}
					
					if (!isset($group['stats']['severities'])) {
						$group['stats']['severities'] = array_fill(0, TRIGGER_SEVERITY_COUNT, 0);
						
						if (!empty($group['problems'])) {
							foreach ($group['problems'] as $problem) {
								if (isset($problem['severity'])) {
									$group['stats']['severities'][$problem['severity']]++;
								}
							}
						}
					}
					
					$group['stats']['problems'] = $group['problems'];
				}
				unset($group);

				$data['groups'] = $tag_groups;
				$data['problems'] = $all_problems ?? [];
			}

			$response_data = [
				'name' => $this->getInput('name', $this->widget->getDefaultName()),
				'error' => null,
				'initial_load' => (bool) $this->getInput('initial_load', 0),
				'data' => $data,
				'filter' => $filter,
				'user' => [
					'debug_mode' => $this->getDebugMode()
				],
				'allowed' => $data['allowed']
			];

			$this->setResponse(new CControllerResponseData($response_data));
		}
	}
}
