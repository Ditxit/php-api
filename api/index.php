
<?php
    include 'doodle/doodle.php';

    // Api to get default token
    Api::get('/token/default', function(){

        // Getting the IP address of the client
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            // ip is from share internet
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        }elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // ip is from proxy
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }else {
            // ip is from remote address
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }

        // Assigning values for token's payload
        $data = [
            'userId'=>-1,
            'userIp'=>$ip_address
        ];

        // Creating the token for non-logged user
        $token = Token::create($data);

        Api::send(
            [
                'success'=>TRUE,
                'content'=>$token
            ]
        );

    });

    // Api to login through email and password
    Api::post('/user/login',function(){

        // Sql string to query the database
        $sql = 'SELECT 
                `id` AS userId, 
                `firstName` AS fName, 
                `middleName` AS mName, 
                `lastName` AS lName 
                FROM `user` 
                WHERE email=? 
                AND passwordHash=?;';

        // Gathering the data from $_POST[] variable -- prepared statement is been used
        $userEmail = $_POST['email'];
        
        // Hashing the password with PASSWORD_BCRYPT alogrithm
        $passwordHash = password_hash($_POST['password'], PASSWORD_BCRYPT);

        // Quering the sql to receive data
        $data = Database::query($sql, $userEmail, $passwordHash);
        // $data eg; [] or ['userid'=>'7','fname'=>'John','mname'=>'Chuckle','lname'=>'Doe']

        // If $data var is not empty
        if($data) {

            // Creating the token for the user
            $token = Token::create($data);

            // Sending the token to user
            Api::send(
                [
                    'success'=>TRUE,
                    'content'=>$token
                ]
            );

        }else{

            // Sending the error message to user
            Api::send(
                [
                    'success'=>FALSE,
                    'message'=>'The user with provided credentials was not found.',
                ]
            );

        } // IF-ELSE

    }); // API -> POST -> /user/login


    // Api to register providing default token
    Api::post('/user/register',function(){
        $data = Database::query('SELECT * FROM `test`');
        Api::send($data);
    });


    // Api to get the product detail from product id
    Api::get('/product/id/([0-9]*)',function($id){

        $verified = Token::verify();
        if(!$verified) {
            Api::send(
                [
                    'success'=>FALSE,
                    'message'=>Token::$error
                ]
            );
        }


        $sql = 'SELECT 
                product.id AS product_id, 
                product.title AS product_title, 
                product.metaTitle AS product_meta_title, 
                product.slug AS product_slug, 
                product.summary AS product_summary, 
                product.type AS product_type, 
                product.sku AS product_sku, 
                product.price AS product_price, 
                product.discount AS product_discount, 
                product.quantity AS product_quantity, 
                product.shop AS product_available, 
                product.createdAt AS product_created_data, 
                product.updatedAt AS product_updated_date, 
                product.publishedAt AS product_published_data, 
                product.startsAt AS product_sale_starting_data, 
                product.endsAt AS product_sale_ending_date, 
                product.content AS product_additional_content, 
                product.userId AS product_uploader_id, 
                user.firstName AS product_uploader_fname, 
                user.middleName AS product_uploader_mname, 
                user.lastName AS product_uploader_lname, 
                image.name AS product_uploader_image 
                FROM product 
                LEFT JOIN user ON product.userId=user.id 
                LEFT JOIN user_image ON user.id=user_image.userId 
                LEFT JOIN image ON user_image.imageId=image.id 
                WHERE product.id=?;
        ';

        $data = Database::query($sql,$id);

        if($data) {

            Api::send(
                [
                    'success'=>TRUE,
                    'content'=>$data[0] # sending only 1st index array
                ]
            );

        }else{

            Api::send(
                [
                    'success'=>FALSE,
                    'message'=>'Product with the provided id does not exist.'
                ]
            );

        }

    });

    // Api to get the product review from product id
    Api::get('/product/id/([0-9]*)/review/([0-9]*)',function($product_id, $review_count){

        $verified = Token::verify();
        if(!$verified) {
            Api::send(
                [
                    'success'=>FALSE,
                    'message'=>Token::$error
                ]
            );
        }

        $sql = 'SELECT 
            id AS product_review_id, 
            parentId AS product_review_parent_id, 
            title AS product_review_title, 
            rating AS product_review_rating, 
            published AS product_review_published, 
            createdAt AS product_review_created_at, 
            publishedAt AS product_review_published_at, 
            content AS product_review_content 
            FROM product_review 
            WHERE productId=? 
            LIMIT '.(int)$review_count.';
        ';

        $data = Database::query($sql, $product_id);

        if($data) {

            Api::send(
                [
                    'success'=>TRUE,
                    'content'=>$data
                ]
            );

        }else{

            Api::send(
                [
                    'success'=>TRUE,
                    'content'=>[]
                ]
            );

        }

    });

    // Api to get the product tags from product id
    Api::get('/product/id/([0-9]*)/tags',function($id){

        $verified = Token::verify();
        if(!$verified) {
            Api::send(
                [
                    'success'=>FALSE,
                    'message'=>Token::$error
                ]
            );
        }

        $sql = 'SELECT 
            tag.id AS tag_id,
            tag.title AS tag_title,
            FROM product_tag 
            LEFT JOIN tag ON product_tag.tagId=tag.id 
            WHERE product_tag.productId=?;
        ';

        $data = Database::query($sql, $id);

        if($data) {

            Api::send(
                [
                    'success'=>TRUE,
                    'content'=>$data
                ]
            );

        }else{

            Api::send(
                [
                    'success'=>FALSE,
                    'content'=>[]
                ]
            );

        }

    });


    // Handeling all other api calls
    Api::get('/([^"]*)',function(){
        Api::send(
            [
                'success'=>FALSE,
                'message'=>'This api endpoint does not exist.'
            ]
        );
    });

    /*

    Api::post('/upload/photo',function(){
        $valid = File::check('jpg','png','jpeg','svg');
        if($valid == TRUE) {
            $name = File::save();
            if($name){
                Api::send($name);
            }else{
                Api::send(File::$error);
            }
        }else {
            Api::send(File::$error);
        }
    });

    Api::post('/product/([0-9]*)',function($id){
        $data = Database::query('SELECT * FROM `test` WHERE id=?;',$id);
        if($data){
            Api::send($data);
        }else{
            Api::send('Data with the id not found');
        }
    });

    Api::post('/login',function(){
        $sql = 'SELECT `id`, `username`, `password` FROM `test` WHERE username=? AND password=?;';
        $data = Database::query($sql,$_POST['username'], $_POST['password']);
        if($data) {
            $token = Token::create($data);
            Api::send($token);
        }else{
            Api::send('Your login credientials does not matched');
        }
    });

    Api::get('/token/verify/([^"]*)',function($token){
        $valid = Token::verify($token);
        if($valid) {
            Api::send($valid);
        }else{
            Api::send('Your token is not valid');
        }

    });
    */

?>

