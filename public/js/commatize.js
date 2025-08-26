function commatize( number, places ) {
    
	if ( isNaN( number ) ) { return ''; } // not a good number string

	var delimiter = ","; // replace comma if desired
	var pieces = number.toString().split('.',2) // array of whole, decimal
	var decimal_part = pieces[1]; 
    // decimal part has to be defined to extend to places
    if ( decimal_part === undefined && places !== undefined && places > 0 ) { decimal_part = 0 }
	var whole_part = parseInt(pieces[0]);
        
	var minus = ''; // remember minus
	if (whole_part < 0) { minus = '-'; }
    
	whole_part = Math.abs( whole_part ); // strip minus
    
	var whole_string = new String(whole_part);
	var three_digit_pieces = [];
    
	while (whole_string.length > 3) // while there's still 3-digit pieces
    {
		var nnn = whole_string.substr( whole_string.length-3 ); // get next 3 digits
		three_digit_pieces.unshift( nnn ); // push them on front of array
		whole_string = whole_string.substr( 0, whole_string.length-3 ); // clip them off the string
	}
	// push on remaining digits
    if (whole_string.length > 0) { three_digit_pieces.unshift(whole_string); } 
    
	new_whole_part = three_digit_pieces.join(delimiter);
    
	if ( decimal_part === undefined || decimal_part.length < 1 ) 
    { 
        number = new_whole_part; // input was integer
    }
    else
    { 
        if ( places !== undefined && places > 0 )
        {
            decimal_part += '000000000000000000';
            decimal_part = decimal_part.substr( 0, places );
        }
        number = new_whole_part + '.' + decimal_part; // input was whole
    }
    
    // cat minus sign on if reqd
	number = minus + number;
	return number;
}