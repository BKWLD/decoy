<?php namespace Bkwld\Decoy\Controllers;

// Run tasks from the admin
class Tasks extends Base {
	
	/**
	 * List all the tasks in the admin
	 */
	public function get_index() {
		$this->layout->nest('content', 'decoy::tasks.index', array(
			'tasks' => \Decoy\Task::all()
		));
	}
	
	/**
	 * Run one of the methods, designed to be called via AJAX
	 * @param $task - The task name, like Feed_Stuff or Seed
	 * @param $method - The name of a method to run, like "auto_approve"
	 */
	public function post_execute($task, $method) {
		$task = \Decoy\Task::find($task);
		$response = $task->run($method);
		return Response::json($response);
	}
	
}