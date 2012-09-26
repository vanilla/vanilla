<?php

if (!defined('APPLICATION'))
   exit();

function ww_CheckPassword($Password, $StoredHash) {
   list($Salt, $Hash) = explode('$', $StoredHash);
   
   $CalcHash = ww_HashEncode($Password.$Salt);
   return $CalcHash == $Hash;
}

function ww_getSalt($intLen) {
   //extract($GLOBALS);;
// Function takes a given length x and generates a random hex value of x digits.
// Salt can be used to help protect passwords.  When a password is first stored in a
// database generate a salt value also.  Concatenate the salt value with the password, 
// and then encrypt it using the HashEncode function below.  Store both the salt value,
// and the encrypted value in the database.  When a password needs to be verified, take 
// the password concatenate the salt from the database.  Encode it using the HashEncode 
// function below.  If the result matches the the encrypted password stored in the
// database, then it is a match.  If not then the password is invalid.
//
//
// Note: Passwords become case sensitive when using this encryption.
// For more information on Password HASH Encoding, and SALT visit: http://local.15seconds.com/issue/000217.htm
//
// Call this function if you wish to generate a random hex value of any given length
//
// Written By: Mark G. Jager
// Written Date: 8/10/2000
//
// Free to distribute as long as code is not modified, and header is kept intact

   $strSalt = '';
   if (!is_numeric($intLen)) {

      $function_ret = "00000000";
      return $function_ret;
   } else
   if (intval($intLen) != doubleval($intLen) || intval($intLen) < 1) {

      $function_ret = "00000000";
      return $function_ret;
   }


   mt_srand((double) microtime() * 1000000);

   for ($intIndex = 1; $intIndex <= intval($intLen); $intIndex = $intIndex + 1) {
      $intRand = intval((mt_rand(0, 10000000) / 10000000)) % 16;
      //echo intval(mt_rand(0,10000000)/10000000);
      $strSalt = $strSalt . ww_getDecHex($intRand);
   }


   $function_ret = $strSalt;

   return $function_ret;
}

function ww_HashEncode($strSecret) {
   //extract($GLOBALS);;
// Function takes an ASCII string less than 2^61 characters long and 
// one way hash encrypts it using 160 bit encryption into a 40 digit hex value.
// The encoded hex value cannot be decoded to the original string value.
//
// This is the only function that you need to call for encryption.
//
// Written By: Mark G. Jager
// Written Date: 8/10/2000
//
// Free to distribute as long as code is not modified, and header is kept intact
//
// The author makes no warranties as to the validity, and/or authenticity of this code.
// You may use any code found herein at your own risk.
// This code was written to follow as closely as possible the standards found in
// Federal Information Processing Standards Publication (FIPS PUB 180-1)
// http://csrc.nist.gov/fips/fip180-1.txt -- Secure Hash Standard SHA-1
//
// This code is for private use only, and the security and/or encryption of the resulting
// hexadecimal value is not warrented or gaurenteed in any way.
//


   if (strlen($strSecret) == 0 || strlen($strSecret) >= pow(2, 61)) {
      return "0000000000000000000000000000000000000000";
   }



//Initial Hex words are used for encoding Digest.  
//These can be any valid 8-digit hex value (0 to F)
   $strH[0] = "FB0C14C2";
   $strH[1] = "9F00AB2E";
   $strH[2] = "991FFA67";
   $strH[3] = "76FA2C3F";
   $strH[4] = "ADE426FA";

   for ($intPos = 1; $intPos <= strlen($strSecret); $intPos = $intPos + 56) {
      $strEncode = substr($strSecret, $intPos - 1, 56); //get 56 character chunks
      $strEncode = ww_WordToBinary($strEncode); //convert to binary
      $strEncode = ww_PadBinary($strEncode); //make it 512 bites
      $strEncode = ww_BlockToHex($strEncode); //convert to hex value
//   decho($strEncode, 'strEncode '.$intPos);
      //print $strEncode."<-<br>";
//Encode the hex value using the previous runs digest
//If it is the first run then use the initial values above
      $strEncode = ww_DigestHex($strEncode, $strH[0], $strH[1], $strH[2], $strH[3], $strH[4]);

//    decho($strEncode, "DigestHex $intPos");
//Combine the old digest with the new digest
      $strH[0] = ww_HexAdd(substr($strEncode, 0, 8), $strH[0]);
      $strH[1] = ww_HexAdd(substr($strEncode, 8, 8), $strH[1]);
      $strH[2] = ww_HexAdd(substr($strEncode, 16, 8), $strH[2]);
      $strH[3] = ww_HexAdd(substr($strEncode, 24, 8), $strH[3]);
      $strH[4] = ww_HexAdd(substr($strEncode, strlen($strEncode) - (8)), $strH[4]);
   }


//This is the final Hex Digest
   $function_ret = $strH[0] . $strH[1] . $strH[2] . $strH[3] . $strH[4];

   return $function_ret;
}

function ww_HexToBinary($btHex) {
   //extract($GLOBALS);;
// Function Converts a single hex value into it's binary equivalent
//
// Written By: Mark Jager
// Written Date: 8/10/2000
//
// Free to distribute as long as code is not modified, and header is kept intact
//

   switch ($btHex) {
      case "0":
         $function_ret = "0000";
         break;
      case "1":
         $function_ret = "0001";
         break;
      case "2":
         $function_ret = "0010";
         break;
      case "3":
         $function_ret = "0011";
         break;
      case "4":
         $function_ret = "0100";
         break;
      case "5":
         $function_ret = "0101";
         break;
      case "6":
         $function_ret = "0110";
         break;
      case "7":
         $function_ret = "0111";
         break;
      case "8":
         $function_ret = "1000";
         break;
      case "9":
         $function_ret = "1001";
         break;
      case "A":
         $function_ret = "1010";
         break;
      case "B":
         $function_ret = "1011";
         break;
      case "C":
         $function_ret = "1100";
         break;
      case "D":
         $function_ret = "1101";
         break;
      case "E":
         $function_ret = "1110";
         break;
      case "F":
         $function_ret = "1111";
         break;
      default:

         $function_ret = "2222";
         break;
   }
   return $function_ret;
}

function ww_BinaryToHex($strBinary) {
   //extract($GLOBALS);;
// Function Converts a 4 bit binary value into it's hex equivalent
//
// Written By: Mark Jager
// Written Date: 8/10/2000
//
// Free to distribute as long as code is not modified, and header is kept intact
//
   switch ($strBinary) {
      case "0000":
         $function_ret = "0";
         break;
      case "0001":
         $function_ret = "1";
         break;
      case "0010":
         $function_ret = "2";
         break;
      case "0011":
         $function_ret = "3";
         break;
      case "0100":
         $function_ret = "4";
         break;
      case "0101":
         $function_ret = "5";
         break;
      case "0110":
         $function_ret = "6";
         break;
      case "0111":
         $function_ret = "7";
         break;
      case "1000":
         $function_ret = "8";
         break;
      case "1001":
         $function_ret = "9";
         break;
      case "1010":
         $function_ret = "A";
         break;
      case "1011":
         $function_ret = "B";
         break;
      case "1100":
         $function_ret = "C";
         break;
      case "1101":
         $function_ret = "D";
         break;
      case "1110":
         $function_ret = "E";
         break;
      case "1111":
         $function_ret = "F";
         break;
      default:

         $function_ret = "Z";
         break;
   }
   return $function_ret;
}

function ww_WordToBinary($strWord) {
   //extract($GLOBALS);;
// Function Converts a 8 digit hex value into it's 32 bit binary equivalent
//
// Written By: Mark Jager
// Written Date: 8/10/2000
//
// Free to distribute as long as code is not modified, and header kept intact
//
   $strBinary = '';
   for ($intPos = 1; $intPos <= strlen($strWord); $intPos = $intPos + 1) {
      $strTemp = substr($strWord, intval($intPos) - 1, 1);
      $strBinary = $strBinary . ww_IntToBinary(ord($strTemp));
   }


   $function_ret = $strBinary;

   return $function_ret;
}

function ww_HexToInt($strHex) {
   //extract($GLOBALS);;
// Function Converts a hex word to its base 10(decimal) equivalent
//
// Written By: Mark Jager
// Written Date: 8/10/2000
//
// Free to distribute as long as code is not modified, and header is kept intact
//


   $intNew = 0;
   $intLen = doubleval(strlen($strHex)) - 1;

   for ($intPos = doubleval($intLen); $intPos >= 0; $intPos = $intPos - 1) {
      switch (substr($strHex, doubleval($intPos) + 1 - 1, 1)) {
         case "0":
            $intNew = doubleval($intNew) + (0 * 16 ^ doubleval($intLen - $intPos));
            break;
         case "1":
            $intNew = doubleval($intNew) + (1 * 16 ^ doubleval($intLen - $intPos));
            break;
         case "2":
            $intNew = doubleval($intNew) + (2 * 16 ^ doubleval($intLen - $intPos));
            break;
         case "3":
            $intNew = doubleval($intNew) + (3 * 16 ^ doubleval($intLen - $intPos));
            break;
         case "4":
            $intNew = doubleval($intNew) + (4 * 16 ^ doubleval($intLen - $intPos));
            break;
         case "5":
            $intNew = doubleval($intNew) + (5 * 16 ^ doubleval($intLen - $intPos));
            break;
         case "6":
            $intNew = doubleval($intNew) + (6 * 16 ^ doubleval($intLen - $intPos));
            break;
         case "7":
            $intNew = doubleval($intNew) + (7 * 16 ^ doubleval($intLen - $intPos));
            break;
         case "8":
            $intNew = doubleval($intNew) + (8 * 16 ^ doubleval($intLen - $intPos));
            break;
         case "9":
            $intNew = doubleval($intNew) + (9 * 16 ^ doubleval($intLen - $intPos));
            break;
         case "A":
            $intNew = doubleval($intNew) + (10 * 16 ^ doubleval($intLen - $intPos));
            break;
         case "B":
            $intNew = doubleval($intNew) + (11 * 16 ^ doubleval($intLen - $intPos));
            break;
         case "C":
            $intNew = doubleval($intNew) + (12 * 16 ^ doubleval($intLen - $intPos));
            break;
         case "D":
            $intNew = doubleval($intNew) + (13 * 16 ^ doubleval($intLen - $intPos));
            break;
         case "E":
            $intNew = doubleval($intNew) + (14 * 16 ^ doubleval($intLen - $intPos));
            break;
         case "F":
            $intNew = doubleval($intNew) + (15 * 16 ^ doubleval($intLen - $intPos));
            break;
      }
   }


   $function_ret = doubleval($intNew);

   return $function_ret;
}

function ww_IntToBinary($intNum) {
   //extract($GLOBALS);;
// Function Converts an integer number to it's binary equivalent
//
// Written By: Mark Jager
// Written Date: 8/10/2000
//
// Free to distribute as long as code is not modified, and header is kept intact
//

   $intNew = $intNum;
   $strBinary = '';
   while ($intNew > 1) {

      $dblNew = doubleval($intNew) / 2;
      $intNew = round(doubleval($dblNew) - 0.1, 0);
      if (doubleval($dblNew) == doubleval($intNew)) {

         $strBinary = "0" . $strBinary;
      } else {

         $strBinary = "1" . $strBinary;
      }
   }

   $strBinary = $intNew . $strBinary;

   $intTemp = strlen($strBinary) % 8;

   for ($intNew = $intTemp; $intNew <= 7; $intNew = $intNew + 1) {
      $strBinary = "0" . $strBinary;
   }


   $function_ret = $strBinary;

   return $function_ret;
}

function ww_PadBinary($strBinary) {
   //extract($GLOBALS);;
// Function adds 0's to a binary string until it reaches 448 bits.
// The lenghth of the original string is incoded into the last 16 bits.
// The end result is a binary string 512 bits long
//
// Written By: Mark Jager
// Written Date: 8/10/2000
//
// Free to distribute as long as code is not modified, and header is kept intact
//


   $intLen = strlen($strBinary);

   $strBinary = $strBinary . "1";

   for ($intPos = strlen($strBinary); $intPos <= 447; $intPos = $intPos + 1) {
      $strBinary = $strBinary . "0";
   }


   $strTemp = ww_IntToBinary($intLen);

   for ($intPos = strlen($strTemp); $intPos <= 63; $intPos = $intPos + 1) {
      $strTemp = "0" . $strTemp;
   }


   $strBinary = $strBinary . $strTemp;

   $function_ret = $strBinary;

   return $function_ret;
}

function ww_BlockToHex($strBinary) {
   //extract($GLOBALS);;
// Function Converts a 32 bit binary string into it's 8 digit hex equivalent
//
// Written By: Mark Jager
// Written Date: 8/10/2000
//
// Free to distribute as long as code is not modified, and header is kept intact
//
   $strHex = '';
   for ($intPos = 1; $intPos <= strlen($strBinary); $intPos = $intPos + 4) {
      $strHex = $strHex . ww_BinaryToHex(substr($strBinary, $intPos - 1, 4));
   }


   $function_ret = $strHex;

   return $function_ret;
}

function ww_DigestHex($strHex, $strH0, $strH1, $strH2, $strH3, $strH4) {
   //extract($GLOBALS);;
// Main encoding function.  Takes a 128 digit/512 bit hex value and one way encrypts it into
// a 40 digit/160 bit hex value.
//
// Written By: Mark Jager
// Written Date: 8/10/2000
//
// Free to distribute as long as code is not modified, and header is kept intact
//
//Constant hex words are used for encryption, these can be any valid 8 digit hex value
   $strK[0] = "5A827999";
   $strK[1] = "6ED9EBA1";
   $strK[2] = "8F1BBCDC";
   $strK[3] = "CA62C1D6";

//Hex words are used in the encryption process, these can be any valid 8 digit hex value
   $strH[0] = $strH0;
   $strH[1] = $strH1;
   $strH[2] = $strH2;
   $strH[3] = $strH3;
   $strH[4] = $strH4;

//divide the Hex block into 16 hex words
   for ($intPos = 0; $intPos <= (strlen($strHex) / 8) - 1; $intPos = $intPos + 1) {
      $strWords[intval($intPos)] = substr($strHex, (intval($intPos) * 8) + 1 - 1, 8);

//    decho($strWords[$intPos], "strWords $intPos");
   }



//encode the Hex words using the constants above
//innitialize 80 hex word positions
   for ($intPos = 16; $intPos <= 79; $intPos = $intPos + 1) {
      $strTemp = $strWords[intval($intPos) - 3];
      $strTemp1 = ww_HexBlockToBinary($strTemp);
      $strTemp = $strWords[intval($intPos) - 8];
      $strTemp2 = ww_HexBlockToBinary($strTemp);
      $strTemp = $strWords[intval($intPos) - 14];
      $strTemp3 = ww_HexBlockToBinary($strTemp);
      $strTemp = $strWords[intval($intPos) - 16];
      $strTemp4 = ww_HexBlockToBinary($strTemp);
      $strTemp = ww_BinaryXOR($strTemp1, $strTemp2);
      $strTemp = ww_BinaryXOR($strTemp, $strTemp3);
      $strTemp = ww_BinaryXOR($strTemp, $strTemp4);
      $strWords[intval($intPos)] = ww_BlockToHex(ww_BinaryShift($strTemp, 1));

//    decho($strWords[$intPos], "strWords2 $intPos");
      //print "<br>->".$strWords[intval($intPos)]."<br>";
   }

//initialize the changing word variables with the initial word variables
   $strA[0] = $strH[0];
   $strA[1] = $strH[1];
   $strA[2] = $strH[2];
   $strA[3] = $strH[3];
   $strA[4] = $strH[4];
//print "<br>->".$strWords[0]."<br>";
//Main encryption loop on all 80 hex word positions
   for ($intPos = 0; $intPos <= 79; $intPos = $intPos + 1) {
      $strTemp = ww_BinaryShift(ww_HexBlockToBinary($strA[0]), 5);
      $strTemp1 = ww_HexBlockToBinary($strA[3]);
      $strTemp2 = ww_HexBlockToBinary($strWords[intval($intPos)]);

//    decho($strTemp, "strTemp $intPos");
//    decho($strTemp1, "strTemp1 $intPos");
//    decho($strTemp2, "strTemp2 $intPos");
      //print "<br>".$intPos." -> ".$strA[4]."<br>";
      switch ($intPos) {
         case 0:
         case 1:
         case 2:
         case 3:
         case 4:
         case 5:
         case 6:
         case 7:
         case 8:
         case 9:
         case 10:
         case 11:
         case 12:
         case 13:
         case 14:
         case 15:
         case 16:
         case 17:
         case 18:
         case 19:
            $strTemp3 = ww_HexBlockToBinary($strK[0]);
            $strTemp4 = ww_BinaryOR(ww_BinaryAND(ww_HexBlockToBinary($strA[1]), ww_HexBlockToBinary($strA[2])), ww_BinaryAND(ww_BinaryNOT(ww_HexBlockToBinary($strA[1])), ww_HexBlockToBinary($strA[3])));
            //echo "<br> - > Case1";
            break;
         case 20:
         case 21:
         case 22:
         case 23:
         case 24:
         case 25:
         case 26:
         case 27:
         case 28:
         case 29:
         case 30:
         case 31:
         case 32:
         case 33:
         case 34:
         case 35:
         case 36:
         case 37:
         case 38:
         case 39:
            $strTemp3 = ww_HexBlockToBinary($strK[1]);
            $strTemp4 = ww_BinaryXOR(ww_BinaryXOR(ww_HexBlockToBinary($strA[1]), ww_HexBlockToBinary($strA[2])), ww_HexBlockToBinary($strA[3]));
            break;
         case 40:
         case 41:
         case 42:
         case 43:
         case 44:
         case 45:
         case 46:
         case 47:
         case 48:
         case 49:
         case 50:
         case 51:
         case 52:
         case 53:
         case 54:
         case 55:
         case 56:
         case 57:
         case 58:
         case 59:
            $strTemp3 = ww_HexBlockToBinary($strK[2]);
            $strTemp4 = ww_BinaryOR(ww_BinaryOR(ww_BinaryAND(ww_HexBlockToBinary($strA[1]), ww_HexBlockToBinary($strA[2])), ww_BinaryAND(ww_HexBlockToBinary($strA[1]), ww_HexBlockToBinary($strA[3]))), ww_BinaryAND(ww_HexBlockToBinary($strA[2]), ww_HexBlockToBinary($strA[3])));
            break;
         case 60:
         case 61:
         case 62:
         case 63:
         case 64:
         case 65:
         case 66:
         case 67:
         case 68:
         case 69:
         case 70:
         case 71:
         case 72:
         case 73:
         case 74:
         case 75:
         case 76:
         case 77:
         case 78:
         case 79:
            $strTemp3 = ww_HexBlockToBinary($strK[3]);
            $strTemp4 = ww_BinaryXOR(ww_BinaryXOR(ww_HexBlockToBinary($strA[1]), ww_HexBlockToBinary($strA[2])), ww_HexBlockToBinary($strA[3]));
            break;
      }

      $strTemp = ww_BlockToHex($strTemp);
      $strTemp1 = ww_BlockToHex($strTemp1);
      $strTemp2 = ww_BlockToHex($strTemp2);
      $strTemp3 = ww_BlockToHex($strTemp3);
      $strTemp4 = ww_BlockToHex($strTemp4);

      $strTemp = ww_HexAdd($strTemp, $strTemp1);

      $strTemp = ww_HexAdd($strTemp, $strTemp2);
      $strTemp = ww_HexAdd($strTemp, $strTemp3);
      $strTemp = ww_HexAdd($strTemp, $strTemp4);

      $strA[4] = $strA[3];
      $strA[3] = $strA[2];
      $strA[2] = ww_BlockToHex(ww_BinaryShift(ww_HexBlockToBinary($strA[1]), 30));
      $strA[1] = $strA[0];
      $strA[0] = $strTemp;
   }


//Concatenate the final Hex Digest
   $function_ret = $strA[0] . $strA[1] . $strA[2] . $strA[3] . $strA[4];

   return $function_ret;
}

function ww_HexAdd($strHex1, $strHex2) {
   $n1 = hexdec($strHex1);
   $n2 = hexdec($strHex2);
   $sum = $n1 + $n2;
   $sum = sprintf("%08X", $sum);
   $sum = substr($sum, -strlen($strHex1));

//   decho("$strHex1 + $strHex2 = $sum", 'HexAdd');

   return $sum;
}

function ww_getHexDec($strHex) {
   //extract($GLOBALS);;
// Function Converts a single hex value into it's decimal equivalent
//
// Written By: Mark Jager
// Written Date: 8/10/2000
//
// Free to distribute as long as code is not modified, and header is kept intact
//
   switch ($strHex) {
      case "0":
         $function_ret = 0;
         break;
      case "1":
         $function_ret = 1;
         break;
      case "2":
         $function_ret = 2;
         break;
      case "3":
         $function_ret = 3;
         break;
      case "4":
         $function_ret = 4;
         break;
      case "5":
         $function_ret = 5;
         break;
      case "6":
         $function_ret = 6;
         break;
      case "7":
         $function_ret = 7;
         break;
      case "8":
         $function_ret = 8;
         break;
      case "9":
         $function_ret = 9;
         break;
      case "A":
         $function_ret = 10;
         break;
      case "B":
         $function_ret = 11;
         break;
      case "C":
         $function_ret = 12;
         break;
      case "D":
         $function_ret = 13;
         break;
      case "E":
         $function_ret = 14;
         break;
      case "F":
         $function_ret = 15;
         break;
      default:

         $function_ret = -1;
         break;
   }
   return $function_ret;
}

function ww_getDecHex($strHex) {
   //extract($GLOBALS);;
// Function Converts a single decimal value(0 - 15) into it's hex equivalent
//
// Written By: Mark Jager
// Written Date: 8/10/2000
//
// Free to distribute as long as code is not modified, and header is kept intact
//
   switch (intval($strHex)) {
      case 0:
         $function_ret = "0";
         break;
      case 1:
         $function_ret = "1";
         break;
      case 2:
         $function_ret = "2";
         break;
      case 3:
         $function_ret = "3";
         break;
      case 4:
         $function_ret = "4";
         break;
      case 5:
         $function_ret = "5";
         break;
      case 6:
         $function_ret = "6";
         break;
      case 7:
         $function_ret = "7";
         break;
      case 8:
         $function_ret = "8";
         break;
      case 9:
         $function_ret = "9";
         break;
      case 10:
         $function_ret = "A";
         break;
      case 11:
         $function_ret = "B";
         break;
      case 12:
         $function_ret = "C";
         break;
      case 13:
         $function_ret = "D";
         break;
      case 14:
         $function_ret = "E";
         break;
      case 15:
         $function_ret = "F";
         break;
      default:

         $function_ret = "Z";
         break;
   }
   return $function_ret;
}

function ww_BinaryShift($strBinary, $intPos) {
   //extract($GLOBALS);;
// Function circular left shifts a binary value n places
//
// Written By: Mark Jager
// Written Date: 8/10/2000
//
// Free to distribute as long as code is not modified, and header is kept intact
//
   $function_ret = substr($strBinary, strlen($strBinary) - (strlen($strBinary) - intval($intPos))) .
   substr($strBinary, 0, intval($intPos));

   return $function_ret;
}

function ww_BinaryXOR($strBin1, $strBin2) {
   //extract($GLOBALS);;
// Function performs an exclusive or function on each position of two binary values
//
// Written By: Mark Jager
// Written Date: 8/10/2000
//
// Free to distribute as long as code is not modified, and header is kept intact
//
   $strBinaryFinal = '';
   for ($intPos = 1; $intPos <= strlen($strBin1); $intPos = $intPos + 1) {
      switch (substr($strBin1, intval($intPos) - 1, 1)) {
         case ww_mid($strBin2, intval($intPos), 1):
            $strBinaryFinal = $strBinaryFinal . "0";
            break;
         default:

            $strBinaryFinal = $strBinaryFinal . "1";
            break;
      }
   }


   $function_ret = $strBinaryFinal;

   return $function_ret;
}

function ww_BinaryOR($strBin1, $strBin2) {
   //extract($GLOBALS);;
// Function performs an inclusive or function on each position of two binary values
//
// Written By: Mark Jager
// Written Date: 8/10/2000
//
// Free to distribute as long as code is not modified, and header is kept intact
//
   $strBinaryFinal = '';
   for ($intPos = 1; $intPos <= strlen($strBin1); $intPos = $intPos + 1) {
      if (substr($strBin1, intval($intPos) - 1, 1) == "1" || substr($strBin2, intval($intPos) - 1, 1) == "1") {

         $strBinaryFinal = $strBinaryFinal . "1";
      } else {

         $strBinaryFinal = $strBinaryFinal . "0";
      }
   }


   $function_ret = $strBinaryFinal;
   return $function_ret;
}

function ww_BinaryAND($strBin1, $strBin2) {
   //extract($GLOBALS);;
// Function performs an AND function on each position of two binary values
//
// Written By: Mark Jager
// Written Date: 8/10/2000
//
// Free to distribute as long as code is not modified, and header is kept intact
//
   $strBinaryFinal = '';
   for ($intPos = 1; $intPos <= strlen($strBin1); $intPos = $intPos + 1) {
      if (substr($strBin1, intval($intPos) - 1, 1) == "1" && substr($strBin2, intval($intPos) - 1, 1) == "1") {

         $strBinaryFinal = $strBinaryFinal . "1";
      } else {

         $strBinaryFinal = $strBinaryFinal . "0";
      }
   }


   $function_ret = $strBinaryFinal;
   return $function_ret;
}

function ww_BinaryNOT($strBinary) {
   //extract($GLOBALS);;
// Function makes each position of a binary value from 1 to 0 and 0 to 1
//
// Written By: Mark Jager
// Written Date: 8/10/2000
//
// Free to distribute as long as code is not modified, and header is kept intact
//
   $strBinaryFinal = '';
   for ($intPos = 1; $intPos <= strlen($strBinary); $intPos = $intPos + 1) {
      if (substr($strBinary, intval($intPos) - 1, 1) == "1") {

         $strBinaryFinal = $strBinaryFinal . "0";
      } else {

         $strBinaryFinal = $strBinaryFinal . "1";
      }
   }


   $function_ret = $strBinaryFinal;

   return $function_ret;
}

function ww_HexBlockToBinary($strHex) {
   //extract($GLOBALS);;
// Function Converts a 8 digit/32 bit hex value to its 32 bit binary equivalent
//
// Written By: Mark Jager
// Written Date: 8/10/2000
// Free to distribute as long as code is not modified, and header is kept intact
//
   $strTemp = '';
   for ($intPos = 1; $intPos <= strlen($strHex); $intPos = $intPos + 1) {
      $strTemp = $strTemp . ww_HexToBinary(substr($strHex, intval($intPos) - 1, 1));
   }


   $function_ret = $strTemp;

   return $function_ret;
}

function ww_left($s, $x) {
   return substr($s, 0, $x);
}

function ww_right($s, $x) {
   return substr($s, $x * -1);
}

function ww_mid($s, $x, $w = "") {  //$w is optional
   if ($w) {
      return substr($s, $x - 1, $w);
   } else {
      return substr($s, $x - 1);
   }
}