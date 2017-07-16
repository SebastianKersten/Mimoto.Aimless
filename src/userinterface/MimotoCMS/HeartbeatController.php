<?php

// classpath
namespace Mimoto\UserInterface\MimotoCMS;

// Mimoto classes
use Mimoto\Mimoto;
use Mimoto\core\CoreConfig;

// Silex classes
use Silex\Application;

// Symfony classes
use Symfony\Component\HttpFoundation\Request;


/**
 * HeartbeatController
 *
 * @author Sebastian Kersten (@supertaboo)
 */
class HeartbeatController
{
    
    public function viewOverview(Request $request, Application $app)
    {
        // 1. prepare
        $sRealtimeScript = Mimoto::value('config')->general->project_root.'src/userinterface/app/javascript/realtime.js';


        Mimoto::output('$sRealtimeScript', $sRealtimeScript);




        // in php file
        // to start the script
        exec("node ".$sRealtimeScript." &", $output);
        //$xx = exec("node realtime.js -v", $output);

        Mimoto::error($output);

        //pcntl_exec ( string $path [, array $args [, array $envs ]] )

        //exec("kill " . $processid);


        // 1. init page
        $page = Mimoto::service('output')->createPage(Mimoto::service('data')->get(CoreConfig::MIMOTO_ROOT, CoreConfig::MIMOTO_ROOT));

        // 2. create content
        $component = Mimoto::service('output')->createComponent('Mimoto.CMS_heartbeat_Overview');

        // 3. connect
        $page->addComponent('content', $component);

        // 4. output
        return $page->render();
    }
    
}