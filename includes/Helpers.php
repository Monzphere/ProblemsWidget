<?php declare(strict_types = 0);

namespace Modules\ProblemsBySvMnz\Includes;

class Helpers {
	public static function getSystemStatusByTags(array $data): array {
		$tags = [];
		$problems_by_tags = [];

		$all_problems = [];
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
								$all_problems[] = $problem;
							}
						}
					}
				}
			}
		}



		foreach ($all_problems as $problem) {
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



		foreach ($tags as $tag_key => $tag_data) {
			$stats = [
				'severities' => $tag_data['severities'],
				'problems' => $tag_data['problems']
			];

			$problems_by_tags[] = [
				'groupid' => md5($tag_key),
				'name' => $tag_data['tag'].': '.$tag_data['value'],
				'problems' => $tag_data['problems'],
				'stats' => $stats,
				'has_problems' => count($tag_data['problems']) > 0
			];

		}


		usort($problems_by_tags, function($a, $b) {
			return strcmp($a['name'], $b['name']);
		});


		foreach ($problems_by_tags as $group) {
			if (!isset($group['stats']['severities'])) {
				error_log('ProblemsBySv Helper: Warning - Missing severities in group: ' . $group['name']);
			}
		}

		return $problems_by_tags;
	}
} 