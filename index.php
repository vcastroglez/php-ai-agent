<?php
require_once 'vendor/autoload.php';

session_start();
if ($_REQUEST['clear']) {
	$_SESSION = [];
}

?>
<!DOCTYPE html>
<html lang="en" >
<head >
	<meta charset="UTF-8" />
	<meta name="viewport"
		  content="width=device-width, 
initial-scale=1.0" />
	<title >Prompt Request</title >
	<style >
		* {
			overflow-x: hidden;
		}
		body {
			margin: 0;
			padding: 0;
			font-family: 'Segoe UI',
			Tahoma, Geneva, Verdana,
			sans-serif;
			background: linear-gradient(135deg, #f0f4f8,
			#e0e8f2);
			display: flex;
			justify-content: center;
			align-items: center;
			height: 95vh;
		}

		.prompt-container {
			background: #ffffff;
			border-radius: 12px;
			box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
			padding: 2rem;
			width: 100%;
			transition: transform 0.2s ease;
			align-content: center;
		}

		.prompt-container:hover {
			transform: scale(1.02);
		}

		h2 {
			text-align: center;
			color: #333;
			margin-bottom: 1.5rem;
		}

		textarea {
			width: 100%;
			padding: 1rem;
			border: 2px solid #e0e8f2;
			border-radius: 8px;
			font-size: 1rem;
			resize: vertical;
			min-height: 100px;
			background: #f9f9f9;
			transition: border-color 0.3s ease;
		}

		textarea:focus {
			border-color: #4CAF50;
			outline: none;
		}

		button {
			width: 100%;
			padding: 1rem;
			background-color: #4CAF50;
			color: white;
			border: none;
			border-radius: 8px;
			font-size: 1rem;
			cursor: pointer;
			transition: background-color 0.3s ease;
		}

		button:hover {
			background-color: #45a049;
		}

		.note {
			text-align: center;
			font-size: 0.9rem;
			color: #666;
			margin-top: 1rem;
		}

		body {
			font-family: Arial, sans-serif;
			background: #f0f4f8;
			margin: 0;
			padding: 20px;
		}

		.output-box {
			display: block;
			max-height: 50vh;
			word-wrap: break-word;
		}
		
		pre {
			padding: 15px;
			background-color: darkslategrey;
			color: white;
		}
	</style >
</head >
<body >
<div class="prompt-container" >
	<div class="output-box" >
		<?php
		$messages = $_SESSION['messages'] ?? [];
		$think = $_SESSION['think'] ?? false;
		$log = $_SESSION['log'] ?? [];
		foreach ($messages as $message) {
			if (!empty($message['tool_name']) || !empty($message['tool_calls'])) continue;
			$parser = new Parsedown();
			$content = $message['content'];
			$content = $parser->text($content);
			?>
			<p >
				<b ><?php echo $message['role']; ?>:</b >
			</p >
			<hr >
			<?php echo $content; ?>
			<?php
		}
		?>
		<p style="display: none" > <?php echo htmlspecialchars(json_encode($messages)); ?>  </p >
	</div >
	<h4 >Enter Your Prompt</h4 >
	<form action="process.php"
		  method="post" >
		<label >
			<textarea name="prompt" placeholder="Type your prompt here..." required ></textarea >
		</label >
		<label >
			<input type="checkbox" name="think" <?php echo ($think ? 'checked' : ''); ?> />
			Think
		</label >
		<button type="submit" >Submit Prompt</button >
	</form >
	<div class="note" >
		<p >Click "Submit Prompt" to
			send your request.</p >
		<a href="/?clear=true" >Clear chat</a >
	</div >
	<h3>Tool call logs:</h3>
	<?php foreach ($log as $line) { ?>
		<p> <?php echo $line; ?>  </p >
	<?php } ?>
</div >
<script>
	document.addEventListener('DOMContentLoaded', () => {
		document.querySelectorAll('code').forEach(code => {
			if(!code.classList.length) return;
			// Make code block relative for absolute positioning
			code.style.display = 'block';
			code.style.position = 'relative';

			// Create copy button
			const button = document.createElement('button');
			button.textContent = '📋';
			button.style.position = 'absolute';
			button.style.top = '8px';
			button.style.right = '8px';
			button.style.zIndex = '10';
			button.style.background = 'rgba(255,255,255,0.8)';
			button.style.border = 'none';
			button.style.padding = '4px 8px';
			button.style.cursor = 'pointer';
			button.style.borderRadius = '4px';
			button.style.fontSize = '14px';
			button.style.width = '30px';

			// Add click handler
			button.addEventListener('click', () => {
				const text = code.textContent || code.innerText;
				navigator.clipboard.writeText(text)
					.then(() => alert('Copied to clipboard!'))
					.catch(err => {
						console.error('Copy failed:', err);
						alert('Failed to copy. Please try manually.');
					});
			});

			// Append button to code block
			code.appendChild(button);
		});
	});
</script>
</body >
</html >
