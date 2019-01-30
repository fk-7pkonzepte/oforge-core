<?php
/*****************************************************
 *        OFORGE
 *      Copyright (c) 7P.konzepte GmbH
 *        License: MIT
 *                (                           (
 *               ( ,)                        ( ,)
 *              ). ( )                      ). ( )
 *             (, )' (.                    (, )' (.
 *            \WWWWWWWW/                  \WWWWWWWW/
 *             '--..--'                    '--..--'
 *                }{                          }{
 *                {}                          {}
 *              _._._                       _._._
 *             _|   |_                     _|   |_
 *             | ... |_._._._._._._._._._._| ... |
 *             | ||| |  o   MUCH FORGE  o  | ||| |
 *             | """ |  """    """    """  | """ |
 *        ())  |[-|-]| [-|-]  [-|-]  [-|-] |[-|-]|  ())
 *       (())) |     |---------------------|     | (()))
 *      (())())| """ |  """    """    """  | """ |(())())
 *      (()))()|[-|-]|  :::   .-"-.   :::  |[-|-]|(()))()
 *      ()))(()|     | |~|~|  |_|_|  |~|~| |     |()))(()
 *         ||  |_____|_|_|_|__|_|_|__|_|_|_|_____|  ||
 *      ~ ~^^ @@@@@@@@@@@@@@/=======\@@@@@@@@@@@@@@ ^^~ ~
 *           ^~^~                                ~^~^
 **********************************************************/

namespace Oforge\Engine\Modules\Core\Abstracts;

/**
 * Class AbstractViewManager
 *
 * @package Oforge\Engine\Modules\Core\Abstracts
 */
abstract class AbstractViewManager {

    /**
     * Assign data from a controller to a zemplate
     *
     * @param array $data
     */
    public abstract function assign($data);

    /**
     * Fetch view data. This function should be called from the route middleware
     * so that it can transport the data to the TemplateEngine
     *
     * @return array
     */
    public abstract function fetch();

    /**
     * Get a specific key value from the viewData
     *
     * @param $key
     *
     * @return mixed
     */
    public abstract function get(string $key);

}
