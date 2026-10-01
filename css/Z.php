<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Document</title>
	<link rel="stylesheet" href="style.css">
</head>
<body>
<form method="post">
	<div id="pytania">
	</div>
	<input type="button" value="Dodaj pytanie" onclick="dodaj_pytanie()"><br>
	<input type="submit" value="Stwórz">
</form>
<script>
		numer_pytania = 1;
		function dodaj_pytanie(){
			pytanie = document.createElement("div");
			pytanie.className = "pytanie";
			pytanie.innerHTML = 
			`
				<input type='text' name='pytanie${numer_pytania}' value='pytanie'>
				<div class='odpowiedzi'>
					<input type='checkbox' name='odp${numer_pytania}_1' value='1'> <input type='text' name='p${numer_pytania}_1' value='odp'><br>
					<input type='button' value='Dodaj odpowiedź' onclick='dodaj_odpowiedz(${numer_pytania}, this)'>
				</div>
			`;
			pytania.appendChild(pytanie);
			numer_pytania++;
		}
		
		function dodaj_odpowiedz(pytanie, przycisk){
			num = przycisk.parentNode.children[przycisk.parentNode.children.length-4];
			console.log(num);
			
			new_radio = document.createElement("input");
			new_input = document.createElement("input");
			br = document.createElement("br");
			
			new_radio.type = "checkbox";
			new_radio.name = `odp${pytanie}_${parseInt(num.value) + 1}`;
			new_radio.value = parseInt(num.value) + 1;
			
			new_input.type = "text";
			new_input.value = "odp";
			new_input.name = `p${pytanie}_${parseInt(num.value) + 1}`
			
			przycisk.parentNode.insertBefore(new_radio, przycisk);
			przycisk.parentNode.insertBefore(new_input, przycisk);
			przycisk.parentNode.insertBefore(br, przycisk);
		}
	</script>
	</body>
</html>