<?php
/**
 * Created by PhpStorm.
 * User: Piotr
 * Date: 2014-05-24
 * Time: 10:29
 */

namespace ReSymf\Bundle\CmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class FileManager
 * @package ReSymf\Bundle\CmsBundle\Controller
 *
 * @author Piotr Francuz <piotr.francuz@bizneslan.pl
 */
class FileManagerController extends Controller
{
    public function getUploadDir() {
        return  $this->get('kernel')->getRootDir() . '/../web/uploads/';
    }

    public function uploadAction()
    {
        $uploadDir = $this->getUploadDir();
        $request = $this->container->get('request');
        $url = $request->headers->get('referer');
        $splitUrl = explode('/', $url);
//        print_r(end($splitUrl));
//        die();
        $file = $request->files->get('file');
        $extension = $file->guessExtension();
        $name = $file->getClientOriginalName();

        $name = preg_replace("([^\w\s\d\-_~,;:\[\]\(\].]|[\.]{2,})", '', $name);

        if (move_uploaded_file($file->getPathName(), $uploadDir.$name)) {
            echo json_encode(array('status'=>'File was uploaded successfuly!', 'fileName' => $name));
        } else {
            echo json_encode(array('status' => 'Something went wrong with your upload!'));
        }
        die();
    }

    public function removeAction()
    {

    }

    public function getExtension($file_name)
    {
        $ext = explode('.', $file_name);
        $ext = array_pop($ext);
        return strtolower($ext);
    }
} 