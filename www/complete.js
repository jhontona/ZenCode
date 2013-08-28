$(function() {
    var availableTags = [
      "Borrar",
      "Consultar"
    ];
    $( "#buscador" ).autocomplete({
      source: availableTags
    });
  });