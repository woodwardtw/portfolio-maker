//from https://stackoverflow.com/questions/44933411/allow-only-letters-numbers-and-hyphen-in-input 
document.getElementById("input_1_3").addEventListener("input", function(e) {
    // Get all inputs in the form we specifically are looking at, this selector can be
    // changed if this is supposed to be applied to specific inputs
    var inputs = document.querySelectorAll('#input_1_3');
    var forbiddenChars = /[^a-z\d\-]/ig;
    
    // check all the inputs we selected
    for(var input of inputs) {
        // Just in case the selector decides to do something weird
        if(!input) continue;
        
        // Check that there aren't any forbidden chars
        if(forbiddenChars.test(input.value)) {
            // This line is just to do something on a failure for Stackoverflow
            // I suggest removing this and doing something different in application
            alert('Your subdomain name had forbidden characters. Please only use lowercase letters, numbers or hyphens.');
            input.value = input.value.replace(forbiddenChars,'');//remove that bad character           
            // Prevent submit even propagation (don't submit)
            //e.preventDefault();
            return false;
        }
    }
  document.getElementById('domain-name').innerHTML = input.value + '.opened.ca';
   textSuccess();
});

function createUrlDiv(){
 let fieldId = document.getElementById('field_1_3');
 let domain = document.createElement("div");
  fieldId.appendChild(domain);
   domain.setAttribute("id", "domain-name");
   domain.innerHTML = 'YOURNAME.opened.ca';
}
createUrlDiv();

document.getElementById("input_1_3").addEventListener("blur", textSuccess);
function textSuccess(){
  let domainName = document.getElementById('domain-name');
  if (domainName.className === "success"){
    domainName.setAttribute("class", "");
  }else {
    domainName.setAttribute("class", "success");  
  }
}
