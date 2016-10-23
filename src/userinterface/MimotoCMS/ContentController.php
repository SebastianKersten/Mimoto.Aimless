<?php

// classpath
namespace Mimoto\UserInterface\MimotoCMS;

// Mimoto classes
use Mimoto\Core\CoreConfig;

// Silex classes
use Silex\Application;


/**
 * ContentController
 *
 * @author Sebastian Kersten (@supertaboo)
 */
class ContentController
{
    
    public function viewContentOverview(Application $app)
    {
        // create
        $page = $app['Mimoto.Aimless']->createComponent('Mimoto.CMS_content_ContentOverview');

        // setup page
        $page->setVar('pageTitle', array(
                (object) array(
                    "label" => 'Content',
                    "url" => '/mimoto.cms/content'
                )
            )
        );

        // output
        return $page->render();
    }
    
}
