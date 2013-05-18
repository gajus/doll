<?php
namespace ay\pdo\debug;

class PDO extends \ay\pdo\PDO {
	private
		$count = 0,
		$query_log = [];
	
	public function __construct($dsn, $username = null, $password = null, array $driver_options = []) {
		parent::__construct($dsn, $username, $password, $driver_options);
		
	    $this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, ['ay\pdo\debug\PdoStatement', [$this]]);
	    
	    parent::exec("SET `profiling` = 1;");
	    parent::exec("SET `profiling_history_size` = 100;");
    }
	
	public function exec ($statement) {
		$this->registerQuery();
		
		return parent::exec($statement);
	}
	
	public function query () {
		$this->registerQuery();
	
		return call_user_func_array(['parent', 'query'], func_get_args());
	}
	
	public function registerQuery () {
		if (++$this->count === 100) {
			$queries = parent::query("SHOW PROFILES;")->fetchAll(\PDO::FETCH_ASSOC);
		
			$this->query_log = array_merge($this->query_log, $queries);
			
			$this->count = 0;
		}
	}
	
	public function __destruct () {
		$total_duration	= 0;
	
		$queries = array_map(function($e) use(&$total_duration) { $e['Duration'] = 1000000*$e['Duration']; $total_duration += $e['Duration']; $e['Query'] = preg_replace('/\s+/', ' ', $e['Query']); return $e; }, $this->query_log);
		
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
		
		?>
		<style>
		.mysql-debug-table { margin: 20px; }
		.mysql-debug-table table { width: 100%; }
		.mysql-debug-table th.id,
		.mysql-debug-table th.duration { width: 100px; }
		.mysql-debug-table tfoot { font-weight: bold; }
		</style>
		<?php
		echo '
		<div class="mysql-debug-table">
			<table>
				<thead>
					<tr>
						<th class="id">Query ID</th>
						<th>Query</th>
						<th class="duration">Duration</th>
					</tr>
				</thead>
				<tbody>';
		foreach($queries as $q):
			echo '<tr><td>' . $q['Query_ID'] . '</td><td>' . $q['Query'] . '</td><td>' . $format_microseconds($q['Duration']) . '</td></tr>';
		endforeach;
		echo '
				</tbody>
				<tfoot>
					<tr>
						<td>' . count($queries) . '</td>
						<td></td>
						<td>' . $format_microseconds($total_duration) . '</td>
					</tr>
				</tfoot>
			</table>
		</div>';
	}
}