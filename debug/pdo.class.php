<?php
namespace ay\pdo\debug;

class PDO extends \ay\pdo\log\PDO {	
	public function onInitialisation () {
		parent::onInitialisation();
	
		\PDO::exec("SET `profiling` = 1;");
		\PDO::exec("SET `profiling_history_size` = 100;");
	}
	
	public function onQuery ($type, $statement, array $parameters = []) {
		parent::onQuery($type, $statement, $parameters);
		
		if ($this->query_log && count($this->query_log) % 100 === 0) {
			$this->addProfileData();
		}
	}
	
	private function addProfileData () {
		if (!$this->isInitialised()) {
			return;
		}
		
		$queries = \PDO::query("SHOW PROFILES;")
			->fetchAll(PDO::FETCH_ASSOC);
		
		foreach ($queries as $q) {
			if ($q['Query'] === 'SET `profiling_history_size` = 100') {
				continue;
			}
			
			$this->query_log[$q['Query_ID'] - 2]['duration'] = 1000000 * $q['Duration'];
			$this->query_log[$q['Query_ID'] - 2]['profile_query'] = $q['Query'];
		}
	}
	
	public function getQueryLog () {
		$this->addProfileData();
		
		return $this->query_log;
	}
	
	public function getQueryLogTable () {
		$this->addProfileData();
		
		require_once __DIR__ . '/sql-formatter-master/lib/SqlFormatter.php';
		
		$total_duration	= array_sum(array_map(function ($e) { return $e['duration']; }, $this->query_log));
		
		$format_microseconds = function ($time) {
			$time = (int) $time;
		
			$pad = FALSE;
			$suffix = 'µs';
		
			if ($time >= 1000) {
				$time = $time / 1000;
				$suffix = 'ms';
				
				if ($time >= 1000) {
					$pad = TRUE;
					
					$time = $time / 1000;
					$suffix = 's';
					
					if ($time >= 60) {
						$time = $time / 60;
						$suffix = 'm';
					}
				}
			}
			
			return $pad ? sprintf('<span class="value">%.4f</span> <span class="measure">' . $suffix . '</span>', $time) : '<span class="value">' . $time . '</span> <span class="measure">' . $suffix . '</span>';
		};
		
		#ay($this->query_log);
		
		ob_start();
		?>
		<style>
		.mysql-debug-table { font-family: monospace; overflow: hidden; }
		
		.mysql-debug-table tr:nth-child(odd) { background: #eee; }
		.mysql-debug-table tr:hover { background: #ffffd1; }
		.mysql-debug-table pre { margin: 0; padding: 5px; white-space: normal; }
		.mysql-debug-table table { width: 100%; border-collapse: collapse; border-spacing: 0; table-layout: fixed; text-align: left; }
		.mysql-debug-table td,
		.mysql-debug-table th { padding: 10px; vertical-align: top; }
		.mysql-debug-table th.id { width: 50px; }
		.mysql-debug-table th.parameters { width: 300px; }
		.mysql-debug-table th.duration { width: 100px; }
		.mysql-debug-table tfoot { font-weight: bold; }
		.mysql-debug-table .mysql-parameters,
		.mysql-debug-table .mysql-plain { overflow: hidden; height: 20px; margin: 0; padding: 0; }
		.mysql-debug-table .mysql-formatted { display: none; }
		.mysql-debug-table .mysql-formatted p { margin: 0 0 10px 0; }
		.mysql-debug-table tr.open .mysql-parameters { height: intrinsic; }
		.mysql-debug-table tr.open .mysql-plain { display: none; }
		.mysql-debug-table tr.open .mysql-formatted { display: block; }
		.mysql-debug-table tr.open pre { white-space: pre; }
		</style>
		<script>
		if (typeof jQuery !== 'undefined') {
			$(function () {
				$('.mysql-debug-table tr').removeClass('open');
				
				$('.mysql-debug-table tr').on('click', function () {
					$(this).toggleClass('open');
				});
			});
		}
		</script>
		<div class="mysql-debug-table">
		<table>
			<thead>
				<tr>
					<th class="id">ID</th>
					<th>Query</th>
					<th class="parameters">Parameters</th>
					<th class="duration">Duration</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($this->query_log as $i => $q): if (empty($q['statement'])) { continue; } ?>
			<tr class="open">
				<td><?=$i + 1?></td>
				<td>
					<div class="mysql-plain"><?=$q['statement']?></div>
					<div class="mysql-formatted">
						<?=\SqlFormatter::format($q['statement'])?>
						<pre><?php foreach ($q['backtrace'] as $t) { if (!isset($t['file'])) { continue; } echo $t['file'] . ' (' . $t['line'] . ')' . "\n"; }?></pre>
					</div>
				</td>
				<td>
					<pre class="mysql-parameters"><?=($q['parameters'] ? json_encode($q['parameters'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) : 'N/A')?></pre>
				</td>
				<td><?=$format_microseconds($q['duration'])?></td>
			</tr>
			<?php endforeach;?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="3"></td>
					<td><?=$format_microseconds($total_duration)?></td>
				</tr>
			</tfoot>
		</table>
		</div>
	<?php
		return ob_get_clean();
	}
}