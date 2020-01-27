$(document).ready(function(){      
     var i=1;  
     $('#add').click(function(){  
          i++;  
          $('#dynamic_field').append('<tr id="row'+i+'" class="dynamic-added"><td><input type="text" name="resource_URLs[]" placeholder="Add another URL..." class="form-control name_list" required /></td><td><button type="button" name="remove" id="'+i+'" class="btn btn-danger btn_remove">Remove</button></td></tr>');  
     });

     $(document).on('click', '.btn_remove', function(){  
          var button_id = $(this).attr("id");   
          $('#row'+button_id+'').remove();  
     });  
}); 