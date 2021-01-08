<?php
session_start();
if(!isset($_SESSION["userid"])) {
    // user not logged in
    header("Location: welcome.php");
    exit();
}

require_once "config.php";

if($conn->connect_errno) {
    echo "Failed to connect to MySQL: " . $conn->connect_error;
    exit();
}

// convert to integer
$user_id = intval($_SESSION["userid"]);

$stmt = $conn->prepare("SELECT * FROM `user` WHERE `id`=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows !== 1) {
    // user does not exist, clear the session
    header("Location: logout.php");
    exit();
}

// fetch every data in the row
$row = $result->fetch_assoc();

// the user has not set up his profile
if($row["age"] === NULL) {
    header("Location: editprofile.php");
    exit();
}

$followers = [];
$following = [];

$stmt_f = $conn->prepare("SELECT `follower_id`, `following_id`  FROM `follower`");
$stmt_f->execute();
$stmt_f->bind_result($follower_id, $following_id);
$stmt_f->store_result();
while($stmt_f->fetch()) {
    if($follower_id === $user_id) {
        // a following user found, query his information
        $stmt_u = $conn->prepare("SELECT `name`, `username`, `avatar` FROM `user` WHERE `id`=?");
        $stmt_u->bind_param("i", $following_id);
        $stmt_u->execute();
        $stmt_u->bind_result($name, $username, $avatar);
        $stmt_u->fetch();
        // store the information
        $following[] = array(
            "userid" => $following_id,
            "name" => $name,
            "username" => $username,
            "avatar" => $avatar
        );
        // close the query
        $stmt_u->close();
    }

    if($following_id === $user_id) {
        // a follower found, query his information
        $stmt_u = $conn->prepare("SELECT `name`, `username`, `avatar` FROM `user` WHERE `id`=?");
        $stmt_u->bind_param("i", $follower_id);
        $stmt_u->execute();
        $stmt_u->bind_result($name, $username, $avatar);
        $stmt_u->fetch();
        // store the information
        $followers[] = array(
            "userid" => $follower_id,
            "name" => $name,
            "username" => $username,
            "avatar" => $avatar
        );
        // close the query
        $stmt_u->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" integrity="sha512-+4zCK9k+qNFUR5X+cKL9EIR+ZOhtIloNl9GIKS57V1MyNsYpYcUrUeQc9vNfzsWfV28IaLL3i96P9sdNyeRssA==" crossorigin="anonymous">
    <link rel="stylesheet" href="/css/main.css">
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.png" />
    <title>PName</title>
</head>

<body>
    <nav class="navbar navbar-expand-md navbar-light bg-light">
        <a class="navbar-brand" href="#">
            <img src="/favicon.ico" width="30" height="30" class="d-inline-block align-top" alt="">
            PName
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar" aria-controls="navbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div id="navbar" class="collapse navbar-collapse">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item active">
                    <a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
                </li>
            </ul>
            <div class="navbar-nav mx-auto">
                <form class="form-inline position-relative">
                    <input id="search" class="form-control mr-sm-2 search-bar" type="search" placeholder="Search" aria-label="Search">
                    <ul id="result" class="list-group search-box d-none"></ul>
                </form>
            </div>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Account
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="/account.php">Account Settings</a>
                        <a class="dropdown-item" href="/logout.php">Logout</a>
                    </div>
                </li>
            </ul>

        </div>
    </nav>
    <div class="container mt-2">
        <div class="row">
            <div class="col-md-8">
                <img src="/avatar/<?php echo $row["avatar"]; ?>" class="rounded mx-auto d-block" width="200" height="200">
                <div class="text-center">
                    <h3 class="mb-0"><?php echo $row["name"]; ?></h3>
                    <p class="text-muted mb-1"><?php echo $row["username"]; ?></p>
                    <p class="text-muted">Joined on <?php echo $row["joined"]; ?></p>
                    <a class="btn btn-primary mb-3" href="/editprofile.php"><i class="fas fa-edit"></i> Edit Profile</a>
                </div>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Bio</h5>
                        <p class="card-text"><?php echo $row["bio"] ?></p>
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-body">
                        <p class="card-text">
                            <i class="fas fa-sm fa-birthday-cake"></i>
                            <?php echo $row["age"]; ?>
                            years old
                        </p>
                        <p class="card-text">
                            <i class="fas fa-sm fa-university"></i>
                            <?php echo $row["education"]; ?>
                        </p>
                        <p class="card-text">
                            <i class="fas fa-sm fa-biking"></i>
                            <?php echo $row["hobbies"]; ?>
                        </p>
                        <p class="card-text">
                            <i class="fas fa-sm fa-language"></i>
                            <?php echo $row["languages"]; ?>
                        </p>
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Working Experience</h5>
                        <p class="card-text"><?php echo $row["working_experience"]; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Following (<?php echo count($following) ?>)</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php
                        if(count($following) === 0) {
                            echo '<li class="list-group-item text-muted">To follow a person, go to their profile and press the Follow button</li>';
                        } else {
                            foreach($following as $item) {
                                ?>
                                <a class="list-group-item list-group-item-action p-1" href="/viewprofile.php?userid=<?php echo $item["userid"]; ?>">
                                    <div class="d-flex align-items-center">
                                        <img src="/avatar/<?php echo $item["avatar"]; ?>" class="rounded mr-2" width="42" height="42">
                                        <div>
                                            <p class="mb-0 d-block text-truncate"><?php echo $item["name"]; ?></p>
                                            <p class="mb-0 text-muted name-height"><?php echo $item["username"]; ?></p>
                                        </div>
                                    </div>
                                </a>
                                <?php
                            }
                        }
                        ?>
                    </ul>
                </div>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Followers (<?php echo count($followers) ?>)</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php
                        if(count($followers) === 0) {
                            echo '<li class="list-group-item text-muted">You have no followers</li>';
                        } else {
                            foreach($followers as $item) {
                                ?>
                                <a class="list-group-item list-group-item-action p-1" href="/viewprofile.php?userid=<?php echo $item["userid"]; ?>">
                                    <div class="d-flex align-items-center">
                                        <img src="/avatar/<?php echo $item["avatar"]; ?>" class="rounded mr-2" width="42" height="42">
                                        <div>
                                            <p class="mb-0 d-block text-truncate"><?php echo $item["name"]; ?></p>
                                            <p class="mb-0 text-muted name-height"><?php echo $item["username"]; ?></p>
                                        </div>
                                    </div>
                                </a>
                                <?php
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx" crossorigin="anonymous"></script>
    <script src="/js/index.js"></script>
</body>

</html>