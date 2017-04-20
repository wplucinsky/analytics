<?php
	
	require_once('sitemapDAO.php');

	class Sitemap {
		// https://www.sitemaps.org/protocol.html

		private $dao;

		private $newPages = array();


		function __construct(){	
			$this->dao = new SitemapDAO();

			$this->getNewPages();
			$this->getPageDetails();
			$this->uploadSitemap();
		}

		public function newPages(){
			return $this->newPages;
		}

		private function getNewPages () {
		/*
			This function gets all the ad that are not in the sitemap table yet.
			Returns an array of ad names.
		*/
			$pages = $this->dao->getNewPages();
			if ($pages) {
				while ($page = $pages->fetch_assoc()) {
					$this->newPages[$page['fullname']] = array('fullname' => $page['fullname']);
				}
			}
		}

		private function getPageDetails() {
		/*
			This function splits up the tasks of getting the `lastmod`,
			`priority` and `nsfw` values
		*/
			foreach ($this->newPages as $key => $page) {
				$this->getLastmod($page);
				$this->getPriority($page);
				$this->getNsfw($page);
			}
		}

		private function getLastmod($adName){
		/*
			This functions gets the date the ad was created.
		*/
			$this->newPages[$adname]['lastmod'] = $this->dao->getLastmod();
		}

		private function getPriority($adname){
		/*
			This functions determines the priority based on number of views.
		*/
			$views = $this->dao->getPriority();
			
			if ( $views > 1000) {
				$this->newPages[$adname]['priority'] = "0.9";
			} elseif ( $views > 500 ) {
				$this->newPages[$adname]['priority'] = "0.8";
			} elseif ( $views > 200 ) {
				$this->newPages[$adname]['priority'] = "0.7";
			} elseif ( $views > 100 ) {
				$this->newPages[$adname]['priority'] = "0.6";
			} else {
				$this->newPages[$adname]['priority'] = "0.5";
			}
		}

		private function getNsfw($adname){
		/*
			This removes the ad from the sitemap if it's marked NSFW.
		*/
			if ( $this->dao->getNsfw() == 1 ){
				unset($this->newPages[$adname]);
			}
		}

		private function uploadSitemap(){
		/*
			This function uploads all the new pages to the sitemap table.
		*/
			return $this->dao->uploadSitemap($this->newPages);
		}

	}
?>