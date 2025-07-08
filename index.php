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
			height: 100vh;
		}

		.prompt-container {
			background: #ffffff;
			border-radius: 12px;
			box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
			padding: 2rem;
			width: 100%;
			max-width: 500px;
			transition: transform 0.2s ease;
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

		.output-box pre {
			display: block;
			white-space: pre-wrap;
			word-wrap: break-word;
			width: fit-content;
		}
	</style >
</head >
<body >
<div class="prompt-container" >
	<div class="output-box">
		<?php 
			$messages = $_SESSION['messages'] ?? [];
			foreach ($messages as $message){
				if(!empty($message['tool_name']) || !empty($message['tool_calls'])) continue;
				$parser = new Parsedown();
				$content = $message['content'];
				$content = $parser->text($content);
				?>
				<p> 
					<b><?php echo $message['role']; ?>:</b>
				</p>
				<p> 
				<?php echo $content; ?>
				</p>
				<?php
			}
		?>
		<p style="display: none"> <?php echo json_encode($messages, JSON_PRETTY_PRINT); ?>  </p>
	</div >
	<h4 >Enter Your Prompt</h4 >
	<form action="process.php"
		  method="post" >
		<label >
			<textarea name="prompt" placeholder="Type your prompt here..." required ></textarea >
		</label >
		<label >
			<input type="checkbox" name="think" />
			Think
		</label >
		<button type="submit" >Submit Prompt</button >
	</form >
	<div class="note" >
		<p >Click "Submit Prompt" to
			send your request.</p >
		<a href="/?clear=true">Clear chat</a>
	</div >
</div >
</body >
</html >
