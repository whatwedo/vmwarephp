<?php
namespace Vmwarephp\Extensions;

class SessionManager extends \Vmwarephp\ManagedObject {

	private $cloneTicketFile;
	private $session = [];

	function acquireSession($userName, $password) {
		if (isset($this->session[$userName])) {
			return $this->session[$userName];
		}
		try {
			$this->session[$userName] = $this->acquireSessionUsingCloneTicket($userName);
		} catch (\Exception $e) {
			$this->session[$userName] = $this->acquireANewSession($userName, $password);
		}
		return $this->session[$userName];
	}

	private function acquireSessionUsingCloneTicket($userName) {
		$cloneTicket = $this->readCloneTicket($userName);
		if (!$cloneTicket) {
			throw new \Exception('Cannot find any clone ticket.');
		}
		return $this->CloneSession(array('cloneTicket' => $cloneTicket));
	}

	private function acquireANewSession($userName, $password) {
		$session = $this->Login(array('userName' => $userName, 'password' => $password, 'locale' => null));
		$cloneTicket = $this->AcquireCloneTicket();
		$this->saveCloneTicket($userName, $cloneTicket);
		return $session;
	}

	private function saveCloneTicket($userName, $cloneTicket) {
		if (!file_put_contents($this->getCloneTicketFile($userName), $cloneTicket))
			throw new \Exception(sprintf('There was an error writing to the clone ticket path. Check the permissions of the cache directory(%s)', __DIR__ . '/../'));
	}

	private function readCloneTicket($userName) {
		$ticketFile = $this->getCloneTicketFile($userName);
		if (file_exists($ticketFile)) {
			return file_get_contents($ticketFile);
		}
	}

	private function getCloneTicketFile($userName) {
		if (!$this->cloneTicketFile) {
			$this->cloneTicketFile = __DIR__ . '/../.clone_ticket-' . str_replace(['\\', '/', '@'], '__', $userName) . '.cache';
		}
		return $this->cloneTicketFile;
	}
}
