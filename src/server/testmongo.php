<?php
echo '<center><h1>PHP+MongoDB Test</h1><br /></center>';
try
{
        $mongo = new Mongo(/*"localahost:27101"*/);

        $blog = $mongo->blog;

        $posts = $blog->posts;

        $it = $posts->find();

        if ($it->count() <1)
        {
                $posts->insert(array('title' => 'Hello, MongoDB!'));
                $posts->insert(array('title' => 'Hello, igame!'));
                $posts->insert(array('title' => 'Hello, php!'));
                $posts->insert(array('title' => 'Hello, Nginx!')); 
        }
        else
        {
                echo $it->count() . ' document(s) found. <br />';

                foreach($it as $obj)
                {
                        echo "title: [" . $obj["title"] . "]<br />";
                }
        }
        $mongo->close();
}
catch(MongoConnectionException $e)
{
        die('Error in connection to MongoDB');
}
catch(MongoException $e)
{
        die('Error:' . $e->getMessage());
}

