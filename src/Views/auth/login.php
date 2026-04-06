<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>כניסה למערכת — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="stylesheet" href="/assets/css/login.css">
</head>
<body class="login-body">

<div class="login-page">
  <div class="login-left">
    <div class="login-brand">
      <div class="login-logo-wrap">
        <i class="fa-solid fa-camera-retro"></i>
      </div>
      <h1>בית הספר לתקשורת</h1>
      <p>מערכת ניהול ציוד</p>
    </div>
    <div class="login-deco">
      <div class="deco-circle c1"></div>
      <div class="deco-circle c2"></div>
      <div class="deco-circle c3"></div>
    </div>
  </div>

  <div class="login-right">
    <div class="login-card">
      <div class="login-card-header">
        <h2>כניסה למערכת</h2>
        <p>הזינו תעודת זהות וסיסמה להמשך</p>
      </div>

      <?php if (!empty($error)): ?>
      <div class="alert alert-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="/login" class="login-form" autocomplete="off">
        <div class="form-group">
          <label for="id_number"><i class="fa-solid fa-id-card"></i> מספר תעודת זהות</label>
          <input
            type="text"
            id="id_number"
            name="id_number"
            class="form-control"
            value="<?= htmlspecialchars($_POST['id_number'] ?? '') ?>"
            placeholder="000000000"
            inputmode="numeric"
            maxlength="9"
            autofocus
            required
          >
        </div>

        <div class="form-group">
          <label for="password"><i class="fa-solid fa-lock"></i> סיסמה</label>
          <div class="input-with-toggle">
            <input
              type="password"
              id="password"
              name="password"
              class="form-control"
              placeholder="••••••••"
              required
            >
            <button type="button" class="toggle-password" onclick="togglePassword()">
              <i class="fa-solid fa-eye" id="eyeIcon"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-login">
          <i class="fa-solid fa-right-to-bracket"></i>
          כניסה למערכת
        </button>
      </form>

      <div class="login-hint">
        <small><i class="fa-solid fa-circle-info"></i> לסיוע בהתחברות פנה/י למנהל המחסן</small>
      </div>
    </div>
  </div>
</div>

<script>
function togglePassword() {
  const input = document.getElementById('password');
  const icon  = document.getElementById('eyeIcon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  }
}
</script>
</body>
</html>
