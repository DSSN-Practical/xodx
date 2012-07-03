<?php

require_once 'Template.php';

class Xodx_FeedController extends Xodx_Controller
{

    /**
     * Returns a Feed in the spezified format (html, rss, atom)
     */
    public function getFeedAction($uri = null, $format = null)
    {
        $this->app = Application::getInstance();
        $bootstrap = $this->app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $request = $bootstrap->getResource('request');

        $nsAair = 'http://xmlns.notu.be/aair#';

        $uri = $request->getValue('uri');
        $format = $request->getValue('format');

        if ($uri !== null) {
            $activitiesResult = $model->sparqlQuery(
                'PREFIX atom: <http://www.w3.org/2005/Atom/> ' .
                'PREFIX aair: <http://xmlns.notu.be/aair#> ' .
                'SELECT ?activity ?date ?verb ?object ' .
                'WHERE { ' .
                '   ?activity a                   aair:Activity ; ' .
                '             aair:activityActor  <' . $uri . '> ; ' .
                '             atom:published      ?date ; ' .
                '             aair:activityVerb   ?verb ; ' .
                '             aair:activityObject ?object . ' .
                '}'
            );

            $activities = array();

            foreach ($activitiesResult as $activity) {
                $activityUri = $activity['activity'];
                $verbUri = $activity['verb'];
                $objectUri = $activity['object'];

                $activity = array(
                    'title' => '"' . $uri . '" did "' . $activity['verb'] . '".',
                    'uri' => $activityUri,
                    'author' => 'Natanael',
                    'authorUri' => $uri,
                    'pubDate' => $activity['date'],
                    'verb' => $activity['verb'],
                    'object' => $activity['object'],
                );

                //echo 'verUri: ' . $verbUri . "\n";
                //echo 'aair: ' . $nsAair . 'Post' . "\n";

                if ($verbUri == $nsAair . 'Post') {
                    //echo 'betrete' . "\n";
                    $objectResult = $model->sparqlQuery(
                        'PREFIX atom: <http://www.w3.org/2005/Atom/> ' .
                        'PREFIX aair: <http://xmlns.notu.be/aair#> ' .
                        'PREFIX sioc: <http://rdfs.org/sioc/ns#> ' .
                        'SELECT ?type ?content ?date ' .
                        'WHERE { ' .
                        '   <' . $objectUri . '> a ?type ; ' .
                        '        sioc:created_at ?date ; ' .
                        '        aair:content ?content . ' .
                        '} '
                    );

                    //var_dump($objectResult);

                    if (count($objectResult) > 0) {
                        $activity['objectType'] = $objectResult[0]['type'];
                        $activity['objectPubDate'] = $objectResult[0]['date'];
                        $activity['objectContent'] = $objectResult[0]['content'];
                    }
                } else {
                }

                $activities[] = $activity;
            }

            $pushController = new Xodx_PushController();

            $template = Template::getInstance();
            $template->setLayout('templates/feed.phtml');
            $template->uri = $uri;
            $template->hub = $pushController->getDefaultHubUrl();
            $template->name = $uri;
            $template->activities = $activities;
            //    $template->render();
        } else {
            // No URI given
        }
    }

}
