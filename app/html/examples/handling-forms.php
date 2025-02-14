<?php
session_start();

// TODO: learn more about csrf tokens and determine if implementation is legitimate
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$name = $email = $age = "";
$birthYear = "";
$ctnPostProcessed = $_SESSION["ctnPostProcessed"] ?? 0;
$nameErr = $emailErr = $ageErr = "";
$formResult = $_SESSION["formResult"] ?? "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
      exit("CSRF validation failed.");
  }
}

if (isset($_POST["clearResults"])) {
  $_SESSION["formResult"] = "";

  header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]));
  exit();
} elseif (isset($_POST["resetCounter"])) {
  $_SESSION["ctnPostProcessed"] = 0;
  http_response_code(204);
  exit();
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Remove accidental whitespace
  $name = trim($_POST["name"] ?? "");
  $email = trim($_POST["email"] ?? "");
  $age = trim($_POST["age"] ?? "");

  // Validate name
  if (empty($name)) {
    $nameErr = "Name is required";
  }

  // Validate email
  if (empty($email)) {
    $emailErr = "Email is required";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $emailErr = "Invalid email format";
  }

  // Validate age
  if ($age && !is_numeric($age)) {
    $ageErr = "Age must be a number";
  } elseif ($age && $age < 0) {
    $ageErr = "Age must be greater than 0";
  }
  // Case non number

  // If no errors, process form
  if (empty($nameErr) && empty($emailErr) && empty($ageErr)) {
    // Can do things with raw data here
    if ($age)
      $birthYear = date("Y") - $age;

    // Escape for HTML display
    $name = htmlspecialchars($name);
    $email = htmlspecialchars($email);

    // Generate success msg
    $formResult = "<b>Submitted form!</b><br>"
      ."<b>Name:</b> $name<br>"
      ."<b>Email:</b> $email";
    if ($age) {
      $formResult .= "<br><b>Age:</b> $age<br>"
        ."<b>Approximate Year of Birth:</b> " .($birthYear - 1) ."-$birthYear";
    }

    $_SESSION["formResult"] = $formResult;
    $_SESSION["ctnPostProcessed"] = ($_SESSION["ctnPostProcessed"] ?? 0) + 1;

    header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]));
    exit();
  }
} else {
  $formResult = $_SESSION["formResult"] ?? "";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handling Forms with PHP</title>
</head>
<body>
  <h2>Example Form</h2>

  <?php if ($formResult) echo "<p>$formResult</p>"; ?>

  <form method="post">
    <label for="name">Name:</label>
    <input type="text" id="name" name="name" value="<?php echo $name; ?>" required>
    <span style="color: red;"><?php echo $nameErr; ?></span>
    <br><br>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" value="" required>
    <span style="color: red;"><?php echo $emailErr; ?></span>
    <br><br>

    <label for="age">Age:</label>
    <input type="number" id="age" name="age" min=0 value="">
    <span style="color: red;"><?php echo $ageErr; ?></span>
    <br><br>

    <input type="submit" value="Submit">
    <input type="submit" name="clearResults" value="Clear Results" formnovalidate>

    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
</form>

<p>Processed Posts: <span id="count"><?php echo $ctnPostProcessed;?></span></p>
<button id="btResetCounter">
  Reset Counter
</button>

<!-- TODO: Include a descirption/tutorial of source code highlighting use of PHP -->

<script>
  document.getElementById("btResetCounter").onclick = e => {
    fetch(
      '<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>',
      {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'resetCounter=true&csrf_token=<?php echo $_SESSION['csrf_token']; ?>'
      }
    ).then(response => {
      if (response.ok) {
        document.getElementById('count').innerHTML = '0';
      }

    }).catch(() => {/*Ignore errors*/});
  };
</script>

</body>
</html>
