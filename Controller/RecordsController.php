<?php

namespace Laposte\DatanovaBundle\Controller;

use Laposte\DatanovaBundle\Model\Download;
use Laposte\DatanovaBundle\Model\Search;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class RecordsController extends Controller
{
    /**
     * @param string $dataset
     * @param string $query
     * @param int $start
     * @param int $rows
     *
     * @return Response
     */
    public function searchAction($dataset, $query, $start, $rows)
    {
        $search = new Search($dataset);
        $search
            ->setQuery($query)
            ->setStart($start)
            ->setRows($rows);
        $results = $this->search($search);

        return new Response(json_encode($results));
    }

    /**
     * @param string $dataset
     * @param string $_format
     * @param string $query
     *
     * @return Response
     */
    public function downloadAction($dataset, $_format, $query)
    {
        $response = new Response();
        $download = new Download($dataset, $_format);
        $download->setFilter($query);
        $local = $this->getLocalDataset($download);
        if ($local) {
            $results = $this->getLocalDataset($download);
        } else {
            $results = $this->download($download);
        }
        switch (strtolower($_format)) {
            case 'json':
                $results = json_encode($results);
                break;
            case 'csv':
                $response->headers->set('Content-Type', 'text/csv');
                break;
        }
        $response->setContent($results);
        $response->headers->set(
            'Content-Disposition',
            sprintf('attachment; filename="%s.%s"', $dataset, $_format)
        );

        return $response;
    }

    /**
     * @param Search $search
     *
     * @return array
     */
    private function search(Search $search)
    {
        return $this
            ->get('data_nova.manager.records')
            ->search($search);
    }

    /**
     * @param Download $download
     *
     * @return array
     */
    private function download(Download $download)
    {
        return $this
            ->get('data_nova.manager.records')
            ->download($download);
    }

    /**
     * @param Download $download
     *
     * @return array
     */
    private function getLocalDataset(Download $download)
    {
        return $this
            ->get('data_nova.manager.records')
            ->getLocalDatasetContent($download);
    }
}