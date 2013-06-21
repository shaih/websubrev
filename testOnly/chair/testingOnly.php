<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

if (isset($_GET['username']) && isset($_GET['password'])) {
  $urlParams = '&username='.$_GET['username'].'&password='.$_GET['password'];
}
else { $urlParams = ''; }

$queries = array(
"INSERT INTO {$SQLprefix}submissions SET subId=1,
  title='On Obfuscating Point Functions',
  authors='Hoeteck Wee',
  abstract='We study the problem of obfuscation in the context of point functions 
(also known as delta functions). A point function is a Boolean
function that assumes the value 1 at exactly one point. Our main
results are as follows:

- We provide a simple construction of efficient obfuscators for
point functions for a slightly relaxed notion of obfuscation - wherein
the size of the simulator has an inverse polynomial dependency on the
distinguishing probability - which is nonetheless impossible for
general circuits. This is the first known construction of obfuscators
for a non-trivial family of functions under general computational
assumptions. Our obfuscator is based on a probabilistic hash function
constructed from a very strong one-way permutation, and does
not require any set-up assumptions. Our construction also yields
an obfuscator for point functions with multi-bit output.

- We show that such a strong one-way permutation - wherein any
polynomial-sized circuit inverts the permutation on at most a
polynomial number of inputs - can be realized using a random
permutation oracle. We prove the result by improving on the counting
argument used in [GT00]\", this result may be of independent
interest. It follows that our construction yields obfuscators for
point functions in the non-programmable random permutation oracle
model (in the sense of [N02]). Furthermore, we prove that an
assumption like the one we used is necessary for our obfuscator
construction.

- Finally, we establish two impossibility results on obfuscating
point functions which indicate that the limitations on our
construction (in simulating only adversaries with single-bit output
and in using non-uniform advice in our simulator) are in some sense
inherent. The first of the two results is a consequence of a simple
characterization of functions that can be obfuscated against general
adversaries with multi-bit output as the class of functions that are
efficiently and exactly learnable using membership queries.

We stress that prior to this work, what is known about obfuscation are
negative results for the general class of circuits [BGI01] and
positive results in the random oracle model [LPS04] or under
non-standard number-theoretic assumptions [C97]. This work
represents the first effort to bridge the gap between the two for a
natural class of functionalities.',
  category='foundations',
  keyWords='obfuscation, point functions',
  whenSubmitted='2005-1-4',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=2, 
  title='Logcrypt: Forward Security and Public Verification for Secure Audit Logs',
  authors='Jason E. Holt; Kent E. Seamons',
  abstract='Logcrypt provides strong cryptographic assurances that data stored by
a logging facility before a system compromise cannot be modified after
the compromise without detection.  We build on prior work by showing
how log creation can be separated from log verification, and
describing several additional performance and convenience features not
previously considered.',
  category='cryptographic protocols',
  keyWords='forward secrecy, audit logs, public-key cryptography',
  whenSubmitted='2005-1-4', lastModified='2005-8-26',
  comments2chair='Added performance section',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=3,
title='Cryptanalysis of Hiji-bij-bij (HBB)',
  authors='Vlastimil Klima',
  abstract='In this paper, we show several known-plaintext attacks on the stream cipher HBB which was proposed recently at INDOCRYPT 2003. The cipher can operate either as a classical stream cipher (in the B mode) or as an asynchronous stream cipher (in the SS mode). In the case of the SS mode, we present known-plaintext attacks recovering 128-bit key with the complexity 2^66 and 256-bit key with the complexity 2^67. In the case of B mode with 256-bit key, we show a known-plaintext attack recovering the whole plaintext with the complexity 2^140. All attacks need only a small part of the plaintext to be known.',
  category='secret-key cryptography',
  keyWords='cryptanalysis, Hiji-bij-bij, HBB, stream ciphers, synchronous cipher, asynchronous cipher,  equivalent keys, known-plaintext attack',
  whenSubmitted='2005-1-5',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=4,
title='Benes and Butterfly schemes revisited',
  authors='Jacques Patarin; Audrey Montreuil',
  abstract='In~\\cite{AV96}, W. Aiello and R. Venkatesan have shown how to
construct pseudo-random functions of \$2n\$ bits \$\\rightarrow 2n\$
bits from pseudo-random functions of \$n\$ bits \$\\rightarrow n\$
bits. They claimed that their construction, called \"Benes\",
reaches the optimal bound (\$m\\ll 2^n\$) of security against
adversaries with unlimited computing power but limited by \$m\$
queries in an adaptive chosen plaintext attack (CPA-2). However a
complete proof of this result is not given in~\\cite{AV96} since
one of the assertions of~\\cite{AV96} is wrong. Due to this, the
proof given in~\\cite{AV96} is valid for most attacks, but not for
all the possible chosen plaintext attacks. In this paper we will
in a way fix this problem since for all \$\\varepsilon>0\$, we will
prove CPA-2 security when \$m\\ll 2^{n(1-\\varepsilon)}\$. However we
will also see that the probability to distinguish Benes functions
from random functions is sometime larger than the term in
\$\\frac{m^2}{2^{2n}}\$ given in~\\cite{AV96}. One of the key idea in
our proof will be to notice that, when \$m\\gg2^{2n/3}\$ and
\$m\\ll2^n\$, for large number of variables linked with some critical
equalities, the average number of solutions may be large (i.e.
\$\\gg1\$) while, at the same time, the probability to have at least
one such critical equalities is negligible (i.e. \$\\ll1\$).',
  keyWords='Pseudo-random functions, unconditional security, information-theoretic primitive, design of keyed hash functions',
  whenSubmitted='2005-1-7',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=5,
title='A sufficient condition for key-privacy',
  authors='Shai Halevi',
  abstract='The notion of key privacy for encryption schemes was defined formally by Bellare, Boldyreva, Desai and Pointcheval in Asiacrypt 2001. This notion seems useful in settings where anonymity is important. In this short note we describe a (very simple) sufficient condition for key privacy. In a nutshell, a scheme that provides data privacy is guaranteed to provide also key privacy if the distribution of a *random encryption of a random message* is independent of the public key that is used for the encryption.',
  category='public-key cryptography',
  keyWords='Anonymity, key-privacy',
  whenSubmitted='2005-1-7',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=6,
title='A Metric on the Set of Elliptic Curves over \${\\mathbf F}_p\$.',
  authors='Pradeep Kumar Mishra; Kishan Chand Gupta',
  abstract='Elliptic Curves over finite field have found application in many areas including cryptography. In the current article we define a metric on the set of elliptic curves defined over a prime field \${\\mathbf F}_p, p>3\$.
',
  category='foundations',
  whenSubmitted='2005-1-10', lastModified='2005-4-15',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=7,
title='The Misuse of RC4 in Microsoft Word and Excel',
  authors='Hongjun Wu',
  abstract='In this report, we point out a serious security flaw in Microsoft Word and Excel. The stream cipher RC4 with key length up to 128 bits is used in Microsoft Word and Excel to protect the documents. But when an encrypted document gets modified and saved, the initialization vector remains the same and thus the same keystream generated from RC4 is applied to encrypt the different versions of that document. The consequence is disastrous since a lot of information of the document could be recovered easily. 
',
  category='applications',
  keyWords='Microsoft Word, Excel, Encryption, RC4, Initialization Vector',
  whenSubmitted='2005-1-10',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=8,
title='Comments on \"Distributed Symmetric Key Management for Mobile Ad hoc Networks\" from INFOCOM 2004',
  authors='J. Wu; R. Wei',
  abstract='In IEEE INFOCOM 2004, Chan proposed a distributed key management
scheme for mobile ad hoc networks, and deduced the condition under
which the key sets distributed to the network nodes can form a
cover-free family (CFF), which is the precondition that the scheme
can work. In this paper, we indicate that the condition is falsely
deduced. Furthermore, we discuss whether CFF is capable for key
distributions in ad hoc networks.',
  category='cryptographic protocols',
  keyWords='Key management',
  whenSubmitted='2005-1-5', lastModified='2005-5-5',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET  subId=9,
  title='Mixing properties of triangular feedback shift registers',
  authors='Bernd Schomburg',
  abstract='The purpose of this note is to show that Markov chains induced by non-singular triangular feedback shift registers and non-degenerate sources are rapidly mixing. The results may directly be applied to the post-processing of random generators and to stream ciphers in CFB mode.',
  category='foundations',
  keyWords='feedback shift registers, stream ciphers, Markov chains, rapid mixing',
  whenSubmitted='2005-1-12',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=10,
title='Update on SHA-1',
  authors='Vincent Rijmen; Elisabeth Oswald',
  abstract='We report on the experiments we performed in order to assess the
security of SHA-1 against the attack by Chabaud and Joux. We present some ideas for optimizations of the attack and some properties of the message expansion routine.
Finally, we show that for a reduced version of SHA-1, with 53
rounds instead of 80, it is possible to find collisions in less
than \$2^{80}\$ operations.',
  category='secret-key cryptography',
  keyWords='hash functions',
  whenSubmitted='2005-1-14',
  comments2chair='This version corrects some errors of the CT-RSA version.',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET  subId=11,
  title='An Improved Elegant Method to Re-initialize Hash Chains',
  authors='Yuanchao Zhao; Daoben Li',
  abstract='Hash chains are widely used in various cryptographic systems such as electronic micropayments and one-time passwords etc. However, hash chains suffer from the limitation that they have a finite number of links which when used up requires the system to re-initialize new hash chains. So system design has to reduce the overhead when hash chains are re-initialized. Recently, Vipul Goyal proposed an elegant one-time-signature-based method to re-initialize hash chains, in this efficient method an infinite number of finite length hash chains can be tied together so that hash chains can be securely re-initialized in a non-repudiable manner. Vipul Goyal¡¯s method is improved in this paper to reach a little more efficient method, which, more importantly, is a natural extension of the concept of conventional hash chains.',
  category='foundations',
  keyWords='hash chains',
  whenSubmitted='2005-1-18',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=12,
title='Efficient Certificateless Public Key Encryption',
  authors='Zhaohui Cheng; Richard Comley',
  abstract='In [3] Al-Riyami and Paterson introduced the notion of \"Certificateless Public Key Cryptography\" and presented an instantiation. In this paper, we revisit the formulation of certificateless public key encryption and construct a more efficient scheme and then extend it to an authenticated
encryption.',
  category='public-key cryptography',
  whenSubmitted='2005-1-19', lastModified='2005-6-6',
  comments2chair='Proofs appended',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=13,
title='Comments: Insider attack on Cheng et al.s pairing-based tripartite key agreement protocols',
  authors='Hung-Yu Chien',
  abstract='Recently, Cheng et al. proposed two tripartite key agreement protocols from pairings: one is certificate-based and the other is identity-based (ID-based). In this article, we show that the two schemes are vulnerable to the insider impersonation attack and the ID-based scheme even discloses the entities¡¦ private keys. Solutions to this problem are discussed.',
  category='cryptographic protocols',
  keyWords='elliptic curve cryptosystem, cryptanalysis, key escrow',
  whenSubmitted='2005-01-20',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=14,
title='A Chosen Ciphertext Attack on a Public Key Cryptosystem Based on Lyndon Words',
  authors='Ludovic Perret',
  abstract='In this paper, we present a chosen ciphertext attack against a 
public key cryptosysten based on Lyndon words \\cite{sm}. We show 
that, provided that an adversary has access to a decryption oracle, 
a key equivalent to the secret key can be constructed efficiently, 
i.e. in linear time.',
  category='public-key cryptography',
  keyWords='cryptanalysis, Lyndon words',
  whenSubmitted='2005-1-20',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=15,
title='Hierarchical Identity Based Encryption with Constant Size Ciphertext',
  authors='Dan Boneh; Xavier Boyen; Eu-Jin Goh',
  abstract='We present a Hierarchical Identity Based Encryption (HIBE) system
where the ciphertext consists of just three group elements and decryption
requires only two bilinear map computations, 
independent of the hierarchy depth.  Encryption is as efficient
as in other HIBE systems. We prove that the scheme is selective-ID secure
in the standard model and fully secure in the random oracle
model.  Our system has a number of applications: it gives very
efficient forward secure public key and identity based cryptosystems (where ciph
ertexts are
short), it converts the NNL broadcast encryption system into an
efficient public key broadcast system, and it provides an efficient
mechanism for encrypting to the future.  The system also supports
limited delegation where users can be given restricted private keys
that only allow delegation to certain descendants.  Sublinear size private
keys can also be achieved at the expense of some ciphertext expansion.
',
  category='public-key cryptography',
  keyWords='Identity Based Encryption',
  whenSubmitted='2005-1-20', lastModified='2005-1-20',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=16,
title='Narrow T-functions',
  authors='Magnus Daum',
  abstract='T-functions were introduced by Klimov and Shamir in a series of papers during the last few years. They are of great interest for cryptography as they may provide some new building blocks which can be used to construct efficient and secure schemes, for example block ciphers, stream ciphers or hash functions.
In the present paper, we define the narrowness of a T-function and study how this property affects the strength of a T-function as a cryptographic primitive.
We define a new data strucure, called a solution graph, that enables solving systems of equations given by T-functions. The efficiency of the algorithms which we propose for solution graphs depends significantly on the narrowness of the involved T-functions.
Thus the subclass of T-functions with small narrowness appears to be weak and should be avoided in cryptographic schemes.
Furthermore, we present some extensions to the methods of using solution graphs, which make it possible to apply these algorithms also to more general systems of equations, which may appear, for example, in the cryptanalysis of hash functions.
',
  keyWords='cryptanalysis, hash functions, solution graph, T-functions, \$w\$-narrow',
  whenSubmitted='2005-1-22', lastModified='2005-1-27',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=17,
title='Side Channel Attacks on Implementations of Curve-Based Cryptographic Primitives',
  authors='Roberto M. Avanzi',
  abstract='The present survey deals with the recent research in side channel
analysis and related attacks on implementations of cryptographic
primitives.  The focus is on software contermeasures for primitives
built around algebraic groups.  Many countermeasures are described,
together with their extent of applicability, and their weaknesses.
Some suggestions are made, conclusion are drawn, some directions for
future research are given.  An extensive bibliography on recent
developments concludes the survey.
',
  category='public-key cryptography',
  keyWords='elliptic curve cryptosystem, hyperelliptic curve cryptosystem, side-channel attacks, countermeasures',
  whenSubmitted='2005-1-23',
  comments2chair='This survey was originally written as a final report of the AREHCC project for the European Commission.',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=18,
title='Collusion Resistant Broadcast Encryption With Short Ciphertexts and Private Keys',
  authors='Dan Boneh; Craig Gentry ; Brent Waters',
  abstract='We describe two new public key broadcast encryption systems for
stateless receivers.  Both systems are fully secure against any number
of colluders. In our first construction both ciphertexts and private
keys are of constant size (only two group elements), for any
subset of receivers.  The public key size in this system is
linear in the total number of receivers.  Our second system is a
generalization of the first that provides a tradeoff between
ciphertext size and public key size.  For example, we achieve a
collusion resistant broadcast system for n users where both
ciphertexts and public keys are of size O(sqrt(n)) for any subset
of receivers.  We discuss several applications of these systems.
',
  category='public-key cryptography',
  whenSubmitted='2005-1-27', lastModified='2005-3-12',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=19,
title='The Full Abstraction of the UC Framework',
  authors='Jes{\\\\\\'u}s F. Almansa',
  abstract='We prove that security in the Universal Composability framework (UC) is equivalent to security in the probabilistic polynomial time calculus ppc. Security is defined under active and adaptive adversaries with synchronous and authenticated communication. In detail, we define an encoding from machines in UC to processes in ppc and show it is fully abstract with respect to UC-security and ppc-security, i.e., we show a protocol is UC-secure iff its encoding is ppc-secure. However, we restrict security in ppc to be quantified not over all possible contexts, but over those induced by UC-environments under encoding. This result is not overly-simplifying security in ppc, since the threat and communication models we assume are meaningful in both practice and theory.',
  category='foundations',
  keyWords='foundations, formal cryptographic analysis',
  whenSubmitted='2005-1-26',
  comments2chair='(DIMACS Title: A Notation for Multiparty Protocols of ITMs: Digging from the Tunnel\'s Other End)',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=20,
title='(De)Compositions of Cryptographic Schemes and their Applications to Protocols',
  authors='R. Janvier; Y. Lakhnech; L. Mazare',
  abstract='The main result of this paper is that the Dolev-Yao model is a safe abstraction of the computational model for security protocols including those that combine asymmetric and symmetric encryption, signature and hashing. Moreover, message forwarding and private key transmission are allowed. To our knowledge this is the first result that deals with hash functions and the combination of these cryptographic primitives. 

A key step towards this result is a general definition of correction of cryptographic primitives, that unifies well known correctness criteria such as IND-CPA, IND-CCA, unforgeability etc.... and a theorem that allows to reduce the correctness of a composition of two cryptographic schemes to the correctness of each one.',
  category='cryptographic protocols',
  keyWords='Security, Cryptographic Protocols, Formal Encryption, Probabilistic Encryption, Dolev-Yao Model, Computational Model',
  whenSubmitted='2005-1-14', lastModified='2005-6-10',
  comments2chair='This revision includes a new simplified proof of the reduction theorem.',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=21,
title='Partial Hiding in Public-Key Cryptography',
  authors='Eabhnat N\'{\\i} Fhloinn; Michael Purser',
  abstract='This paper explores the idea of partially exposing sections of the private key in public-key cryptosystems whose security is based on the intractability of factorising large integers.
It is proposed to allow significant portions of the private key to be publicly available, reducing the amount of data which must be securely hidden.
The \"secret\" data could be XORed with an individual\'s biometric reading in order to maintain a high level of security, and we suggest using iris templates for this purpose.
Finally, we propose an implementation of this system for RSA, and consider the potential risks and advantages associated with such a scheme.',
  category='public-key cryptography',
  keyWords='public-key cryptography, RSA, partial key exposure, partial hiding, iris, biometrics',
  whenSubmitted='2005-1-25', lastModified='2005-2-2',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=22,
title='An Improved and Efficient Countermeasure against Power Analysis Attacks',
  authors='ChangKyun Kim; JaeCheol Ha; SangJae Moon; Sung-Ming Yen; Wei-Chih Lien;  Sung-Hyun Kim',
  abstract='Recently new types of differential power analysis attacks (DPA)
against elliptic curve cryptosystems (ECC) and RSA systems have been
introduced. Most existing countermeasures against classical DPA
attacks are vulnerable to these new DPA attacks which include
refined power analysis attacks (RPA), zero-value point attacks
(ZPA), and doubling attacks. The new attacks are different from
classical DPA in that RPA uses a special point with a zero-value
coordinate, while ZPA uses auxiliary registers to locate a zero
value. So, Mamiya et al proposed a new countermeasure against RPA,
ZPA, classical DPA and SPA attacks using a basic random initial
point. His countermeasure works well when applied to ECC, but it has
some disadvantages when applied to general exponentiation algorithms
(such as RSA and ElGamal) due to an inverse computation. This paper
presents an efficient and improved countermeasure against the above
new DPA attacks by using a random blinding concept on the message
different from Mamiya\'s countermeasure and show that our proposed
countermeasure is secure against SPA based Yen\'s power analysis
which can break Coron\'s simple SPA countermeasure as well as
Mamiya\'s one. The computational cost of the proposed scheme is very
low when compared to the previous methods which rely on Coron\'s
simple SPA countermeasure. Moreover this scheme is a generalized
countermeasure which can be applied to ECC as well as RSA system.',
  keyWords='Side channel attack, DPA, RPA, ZPA, doubling attack, SPA, ECC, RSA',
  whenSubmitted='2005-1-25',
  comments2chair='The proposed countermeasure described in this paper was more efficient and secure than Mamiya\'s countermeasure(BRIP) of CHES 2004.',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=23,
title='A Construction of Public-Key Cryptosystem Using Algebraic Coding on the Basis of Superimposition and Randomness',
  authors='Masao Kasahara',
  abstract='In this paper, we present a new class of public-key cryptosystem (PKC) using algebraic coding on the basis of superimposition and randomness. The proposed PKC is featured by a generator matrix, in a characteristic form, where the generator matrix of an algebraic code is repeatedly used along with the generator matrix of a random code, as sub-matrices. This generator matrix, in the characteristic form, will be referred to as \$K\$-matrix. We show that the \$K\$-matrix yields the following advantages compared with the conventional schemes:
\\\\begin{description}
\\\\item [(i)] It realizes an abundant supply of PKCs, yielding more secure PKCs.
\\\\item [(i\\\\hspace{-.1em}i)] It realizes a fast encryption and decryption process.
\\end{description}',
  category='public-key cryptography',
  keyWords='algebraic coding, random coding, public-key cryptosystem
Publication Info. SCIS 2005 (The 2005 Symposium on Cryptography and Information Security)',
  whenSubmitted='2005-1-28',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=24,
title='On the Diffie-Hellman problem over \$GL_{n}\$',
  authors='A. A. Kalele; V. R. Sule',
  abstract='This paper considers the Diffie-Hellman problem (DHP) over the
matrix group \$\\gln\$ over finite fields and shows that for matrices
\$A\$ and exponents \$k\$, \$l\$ satisfying certain conditions called
the \\emph{modulus conditions}, the problem can be solved without
solving the discrete logarithm problem (DLP) involving only
polynomial number of operations in \$n\$. A specialization of this
result to DHP on \$\\fpm^*\$ shows that there exists a class of
session triples of a DH scheme for which the DHP can be solved in
time polynomial in \$m\$ by operations over \$\\fp\$ without solving
the DLP. The private keys of such triples are termed \\emph{weak}.
A sample of weak keys is computed and it is observed that their
number is not too insignificant to be ignored. Next a
specialization of the analysis is carried out for pairing based DH
schemes on supersingular elliptic curves and it is shown that for
an analogous class of session triples, the DHP can be solved
without solving the DLP in polynomial number of operations in the
embedding degree. A list of weak parameters of the DH scheme is
developed on the basis of this analysis.',
  category='public-key cryptography',
  keyWords='Diffie Hellman problem , pairing based Diffie Hellman key exchange',
  whenSubmitted='2005-1-27',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=25,
title='Analysis of Affinely Equivalent Boolean Functions',
  authors='Meng Qing-shu; Yang min; Zhang Huan-guo; Liu Yu-zhen',
  abstract='By walsh
transform, autocorrelation function, decomposition, derivation and
modification of truth table, some new invariants are obtained.
Based on invariant theory, we get two results: first a general
algorithm which can be used to judge if two boolean functions are
affinely equivalent and to obtain the affine equivalence
relationship if they are equivalent. For example, all 8-variable
homogenous bent functions of degree 3 are classified into 2
classes\", second, the classification of the Reed-Muller code
\$R(4,6)/R(1,6),R(3,7)/R(1,7),\$ which can be used to almost
enumeration of 8-variable bent functions.',
  category='foundations',
  keyWords='boolean functions,linearly equivalent, affine group',
  whenSubmitted='2005-30 Jan', lastModified='2005-27 Apr',
  comments2chair='a wrong word in title is corrected',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=26,
title='Techniques for random maskin in hardware',
  authors='Jovan Dj. Golic',
  abstract='A new technique for Boolean random masking of the logic AND operation in terms of NAND logic gates
is presented and its potential for masking arbitrary cryptographic functions is pointed out. 
The new technique is much more efficient than a previously known technique, recently applied to AES. 
It is also applied for masking the integer addition. 
In addition, new techniques for the conversions from Boolean to arithmetic random masking and vice versa
are developed. They are hardware oriented and do not require additional random bits. 
Unlike the previous, software-oriented techniques showing a substantial difference in the complexity
of the two conversions, they have a comparable complexity being about the same as that
of one integer addition only. 
All the techniques proposed are in theory secure against the first-order differential
power analysis on the logic gate level. 
They can be applied in hardware implementations of various cryptographic functions,
including AES, (keyed) SHA-1, IDEA, and RC6.',
  category='implementation',
  keyWords='power analysis, random masking, logic circuits',
  whenSubmitted='2005-2 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=27,
title='Tag-KEM/DEM: A New Framework for Hybrid Encryption',
  authors='Masayuki ABE; Rosario Gennaro; Kaoru Kurosawa',
  abstract='This paper presents a novel framework for generic construction of hybrid encryption schemes which produces more efficient schemes than before.  A known framework introduced by Shoup combines a key encapsulation mechanism (KEM) and a data encryption mechanism (DEM). While it is believed that both of the components must be secure against chosen ciphertext attacks, Kurosawa and Desmedt showed a particular example of KEM that might not be CCA but can be securely combined with CCA DEM yielding more efficient hybrid encryption scheme.  There are also many efficient hybrid encryption schemes in various settings that do not fit to the framework.  These facts serve as motivation to seek another framework that yields more efficient schemes.

In addition to the potential efficiency of the resulting schemes, our
framework will provide insightful explanation about existing schemes
that do not fit to the previous framework.  This could result in finding improvements for some schemes.  Moreover, it allows immediate conversion from a class of threshold public-key encryption to a hybrid one without considerable overhead, which is not possible in the previous approach.
',
  category='public-key cryptography',
  keyWords='hybrid encryption',
  whenSubmitted='2005-3 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=28,
title='Improved Proxy Re-Encryption Schemes with Applications to Secure Distributed Storage',
  authors='Giuseppe Ateniese; Kevin Fu; Matthew Green; Susan Hohenberger',
  abstract='In 1998, Blaze, Bleumer, and Strauss (BBS) proposed an application called
atomic proxy re-encryption, in which a semi-trusted proxy
converts a ciphertext for Alice into a ciphertext for Bob without 
seeing the underlying plaintext.  We predict that fast and
secure re-encryption will become increasingly popular as a method for
managing encrypted file systems.  Although efficiently computable, the
wide-spread adoption of BBS re-encryption has been hindered by
considerable security risks.  Following recent work of Ivan and Dodis,
we present new re-encryption schemes that realize a stronger notion of
security and we demonstrate the usefulness of proxy re-encryption as a
method of adding access control to the SFS read-only file system.
Performance measurements of our experimental file system demonstrate
that proxy re-encryption can work effectively in practice.',
  whenSubmitted='2005-3 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=29,
title='A model and architecture for pseudo-random generation with applications to /dev/random',
  authors='Boaz Barak; Shai Halevi',
  abstract='We present a formal model and a simple architecture for robust pseudorandom generation that ensures resilience in the face of an
observer with partial knowledge/control of the generator\'s entropy source. Our model and architecture have the following properties:


1 Resilience:  The generator\'s output looks random to an observer with no knowledge of the internal state. This holds even if that observer has complete control over data that is used to refresh the internal state.

2 Forward security: Past output of the generator looks random to an observer, even if the observer learns the internal state at a later time.

3 Backward security/Break-in recovery: Future output of the generator looks random, even to an observer with knowledge of the current state, provided that the generator is refreshed with data of sufficient entropy.


Architectures such as above were suggested before. This work differs
from previous attempts in that we present a formal model for robust
pseudo-random generation, and provide a formal proof within this model
for the security of our architecture. To our knowledge, this is the
first attempt at a rigorous model for this problem.

Our formal modeling advocates the separation of the *entropy extraction* phase from the *output generation* phase. We argue that the former is information-theoretic in nature, and could therefore rely on combinatorial and statistical tools rather than on cryptography. On the other hand, we show that the latter can be implemented using any standard (non-robust) cryptographic PRG.

We also discuss the applicability of our architecture for applications such as /dev/(u)random in Linux and pseudorandom generation on smartcards.
',
  keyWords='/dev/random, Entropy, Mixing functions,Pseudo-randomness, Smart-cards, True randomness.',
  whenSubmitted='2005-5 Feb', lastModified='2005-1 Sep',
  comments2chair='Minor revision',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=30,
title='Weak keys of pairing based Diffie Hellman schemes on elliptic curves',
  authors='A. A. Kalele; V. R. Sule',
  abstract='This paper develops a cryptanalysis of the pairing based Diffie
Hellman (DH) key exchange schemes an instance of which is the
triparty single round key exchange proposed in \\cite{joux}. The
analysis of \\emph{weak sessions} of the standard DH scheme
proposed in \\cite{kasu} is applied to show existence of weak
sessions for such schemes over supersingular curves. It is shown
that for such sessions the associated Bilinear Diffie Hellman
Problem is solvable in polynomial time, without computing the
private keys i.e. without solving the discrete logarithms. Other
applications of the analysis to Decisional Diffie Hellman Problem
and the identitiy based DH scheme are also shown to hold. The
triparty key exchange scheme is analyzed for illustration and it
is shown that the number of weak keys increases in this scheme as
compared to the standard two party DH scheme. It is shown that the
random choice of private keys by the users independent of each
other\'s knowledge is insecure in these schemes. Algorithms are
suggested for checking weakness of private keys based on an order
of selection.',
  category='public-key cryptography',
  keyWords='Bilinear Diffie-Hellman problem, Triparty key exchange',
  whenSubmitted='2005-7 Feb', lastModified='2005-10 Feb',
  comments2chair='Submitting the revised copy as we found a mistake in references.',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=31,
title='The Vector Decomposition Problem for Elliptic and Hyperelliptic Curves',
  authors='Iwan Duursma; Negar Kiyavash',
  abstract='The group of m-torsion points on an elliptic curve, for a prime
number m, forms a two-dimensional vector space. It was suggested
and proven by Yoshida that under certain conditions the vector
decomposition problem (VDP) on a two-dimensional vector space is
at least as hard as the computational Diffie-Hellman problem
(CDHP) on a one-dimensional subspace. In this work we show that
even though this assessment is true, it applies to the VDP for
m-torsion points on an elliptic curve only if the curve is
supersingular. But in that case the CDHP on the one-dimensional
subspace has a known sub-exponential solution. Furthermore, we
present a family of hyperelliptic curves of genus two that are
suitable for the VDP.',
  category='public-key cryptography',
  keyWords='Elliptic curve cryptography, Curves of genus two',
  whenSubmitted='2005-7 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=32, 
  title='On the Notion of Statistical Security in Simulatability Definitions',
  authors='Dennis Hofheinz; Dominique Unruh',
  abstract='  We investigate the definition of statistical security (i.e.,
  security against unbounded adversaries) in the framework of reactive
  simulatability. This framework allows to formulate and analyze
  multi-party protocols modularly by providing a composition theorem
  for protocols. However, we show that the notion of statistical
  security, as defined by Backes, Pfitzmann and Waidner for the
  reactive simulatability framework, does not allow for secure
  composition of protocols. This in particular invalidates the proof
  of the composition theorem.

  We give evidence that the reason for the non-composability of
  statistical security is no artifact of the framework itself, but of
  the particular formulation of statistical security. Therefore, we
  give a modified notion of statistical security in the reactive
  simulatability framework. We prove that this notion allows for
  secure composition of protocols.

  As to the best of our knowledge, no formal definition of statistical
  security has been fixed for Canetti\'s universal composability
  framework, we believe that our observations and results can also
  help to avoid potential pitfalls there.
',
  category='cryptographic protocols',
  keyWords='Reactive simulatability, universal composability, statistical security, protocol composition',
  whenSubmitted='2005-7 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=33,
title='A Flexible Framework for Secret Handshakes',
  authors='Gene Tsudik; Shouhuai Xu',
  abstract='In the society increasingly concerned with the erosion of privacy,
privacy-preserving techniques are becoming very important.
Secret handshakes offer anonymous and unobservable authentication 
and serve as an important tool in the arsenal of privacy-preserving 
techniques. Relevant prior research focused on 2-party secret 
handshakes with one-time credentials, whereby two parties establish 
a secure, anonymous and unobservable communication channel only if 
they are members of the same group. 

This paper breaks new ground on two accounts: (1) it shows how 
to obtain secure and efficient secret handshakes with reusable 
credentials, and (2) it provides the first treatment of multi-party
secret handshakes, whereby m>=2 parties establish a secure, 
anonymous and unobservable communication channel only if they all
belong to the same group. An interesting new issue encountered 
in multi-party secret handshakes is the need to ensure that all
parties are distinct. (This is a real challenge since the 
parties cannot expose their identities.) We tackle this and 
other challenging issues in constructing GCD -- a flexible secret 
handshake framework. \\GCD\\ can be viewed as a compiler that 
transforms three main building blocks: (1) a Group signature scheme, 
(2) a Centralized group key distribution scheme, and (3) a 
Distributed group key agreement scheme, into a secure 
multi-party secret handshake scheme.

The proposed framework lends itself to multiple practical 
instantiations, and offers several novel and appealing features 
such as self-distinction and strong anonymity with reusable
credentials. In addition to describing the motivation and 
step-by-step construction of the framework, this paper provides 
a security analysis and illustrates several concrete framework 
instantiations.',
  category='cryptographic protocols',
  keyWords='secret handshakes, privacy-preservation, anonymity, credential systems, unobservability, unlinkability, key management',
  whenSubmitted='2005-8 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=34,
title='An Efficient CDH-based Signature Scheme With a Tight Security Reduction',
  authors='Benoit Chevallier-Mames',
  abstract='At Eurocrypt 03, Goh and Jarecki showed that, contrary to other 
signature schemes in the discrete-log setting, the EDL signature 
scheme has a tight security reduction, namely to the 
Computational Diffie-Hellman (CDH) problem, in the Random Oracle 
(RO) model.  They also remarked that EDL can be turned into an 
off-line/on-line signature scheme using the technique of Shamir 
and Tauman, based on chameleon hash functions.

In this paper, we propose a new signature scheme that also has a 
tight security reduction to CDH but whose resulting signatures 
are smaller than EDL signatures.  Further, similarly to the 
Schnorr signature scheme (but contrary to EDL), our signature is 
naturally efficient on-line: no additional trick is needed for 
the off-line phase and the verification process is unchanged.

For example, in elliptic curve groups, our scheme results in a 
25% improvement on the state-of-the-art discrete-log based 
schemes, with the same security level.  This represents to date 
the most efficient scheme of any signature scheme with a tight 
security reduction in the discrete-log setting.',
  keyWords='signature schemes, discrete logarithm problem, Diffie-Hellman problem, EDL',
  whenSubmitted='2005-10 Feb', lastModified='2005-30 May',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=35,
title='Concurrent Composition of Secure Protocols in the Timing Model',
  authors='Yael Kalai; Yehuda Lindell; Manoj Prabhakaran',
  abstract='In the setting of secure multiparty computation, a set of mutually
distrustful parties wish to securely compute some joint function
of their inputs. In the stand-alone case, it has been shown that
{\\em every} efficient function can be securely computed.
However, in the setting of concurrent composition, broad
impossibility results have been proven for the case of no honest
majority and no trusted setup phase. These results hold both for
the case of general composition (where a secure protocol is run
many times concurrently with arbitrary other protocols) and self
composition (where a single secure protocol is run many times
concurrently).

In this paper, we investigate the feasibility of obtaining
security in the concurrent setting, assuming that each party has a
local clock and that these clocks proceed at approximately the
same rate. We show that under this mild timing assumption, it is
possible to securely compute {\\em any} multiparty functionality
under concurrent \\emph{self} composition. We also show that it
is possible to securely compute {\\em any} multiparty
functionality under concurrent {\\em general} composition, as
long as the secure protocol is run only with protocols whose
messages are delayed by a specified amount of time. On the
negative side, we show that it is impossible to achieve security
under concurrent general composition with no restrictions
whatsoever on the network (like the aforementioned delays), even
in the timing model.',
  category='cryptographic protocols',
  keyWords='multiparty computation, concurrent general composition, timing model',
  whenSubmitted='2005-10 Feb', lastModified='2005-28 Jul',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=36,
title='Improving Secure Server Performance by Re-balancing SSL/TLS Handshakes',
  authors='Claude Castelluccia; Einar Mykletun; Gene Tsudik',
  abstract='Much of today\'s distributed computing takes place in a client/server model.
Despite advances in fault tolerance -- in particular, replication and load
distribution -- server overload remains to be
a major problem. In the Web context, one of the main overload factors is the
direct consequence of expensive Public Key operations performed by servers
as part of each SSL handshake. Since most SSL-enabled servers use RSA,
the burden of performing many costly decryption operations can be
very detrimental to server performance. This paper examines a
promising technique for re-balancing RSA-based client/server
handshakes. This technique facilitates more favorable load distribution
by requiring clients to perform more work (as part of encryption) and
servers to perform commensurately less work, thus resulting in better
SSL throughput.  Proposed techniques are based on careful adaptation of
variants of Server-Aided RSA originally constructed by
Matsumoto, et al. Experimental results demonstrate that
suggested methods (termed Client-Aided RSA) can speed up processing
by a factor of between 11 to 19, depending on the RSA key size. This represents
a considerable improvement. Furthermore, proposed techniques can be a useful
companion tool for SSL Client Puzzles in defense against DoS and DDoS attacks.',
  category='public-key cryptography',
  keyWords='SSL, RSA, Client-aided',
  whenSubmitted='2005-10 Feb', lastModified='2005-8 Apr',
  comments2chair='Contrary to \"popular belief\", our proposed solution is not subject to the 
meet-in-the-middle attack proposed in private communication with David Wagner.',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=37,
title='Distinguishing Stream Ciphers with Convolutional Filters',
  authors='Joan Daemen; Gilles Van Assche',
  abstract='This paper presents a new type of distinguisher for the shrinking generator and the alternating-step generator with known feedback polynomial and for the multiplexor generator. For the former the distinguisher is more efficient than existing ones and for the latter it results in a complete breakdown of security. The distinguisher is conceptually very simple and lends itself to theoretical analysis leading to reliable predictions of its probability of success.
',
  category='secret-key cryptography',
  keyWords='Stream ciphers, cryptanalysis',
  whenSubmitted='2005-15 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=38,
title='Unfairness of a protocol for certified delivery',
  authors='Juan M. Estevez-Tapiador; Almudena Alcaide',
  abstract='Recently, Nenadi\'c \\emph{et al.} (2004) proposed the RSA-CEGD
protocol for certified delivery of e-goods. This is a relatively
complex scheme based on verifiable and recoverable encrypted
signatures (VRES) to guarantee properties such as strong fairness
and non-repudiation, among others. In this paper, we demonstrate how
this protocol cannot achieve fairness by presenting a severe attack
and also pointing out some other weaknesses.',
  category='cryptographic protocols',
  keyWords='fair exchange, non-repudiation, attacks',
  whenSubmitted='2005-15 Feb', lastModified='2005-16 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=39,
title='On the Security of a Group Signature Scheme with Strong Separability',
  authors='Lihua Liu; Zhengjun Cao',
  abstract='A group signature scheme allows a
      group member of a given group to sign messages on behalf of
      the group in an anonymous and unlinkable fashion. In case of
      a dispute, however, a designated group manager can reveal
      the signer of a valid group signature. Many applications of
      group signatures require that the group manager can be split
      into a membership manager and a revocation manager. Such a
      group signature scheme with strong separability was proposed
      in paper [1]. Unfortunately, the scheme is insecure which has   been shown in [2][3][4]. In this  paper
 we show that the scheme  is untraceable by a simple and direct attack.  Besides, we show its universal forgeability by a
       general attack which only needs to choose five random numbers.
        We minutely explain the technique to shun the challenge in
       the scheme.',
  category='cryptographic protocols',
  keyWords='Group signature, Untraceability,Universal forgeability.',
  whenSubmitted='2005-15 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=40,
  title='Polyhedrons over Finite Abelian Groups and Their Cryptographic Applications',
  authors='Logachev~O.A.; Salnikov~A.A.; Yaschenko~V.V.',
  abstract='We are using the group-theory methods for justification of
algebraic method in cryptanalysis. The obtained results are using
for investigation of  Boolean functions cryptographic properties.',
  category='secret-key cryptography',
  keyWords='boolean functions, cryptanalisis, discrete functions',
  whenSubmitted='2005-16 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=41,
title='An Efficient Solution to The Millionaires Problem Based on Homomorphic Encryption',
  authors='Hsiao-Ying Lin; Wen-Guey Tzeng',
  abstract='We proposed a two-round protocol for solving the
 Millionaires Problem in the setting of semi-honest
 parties.
Our protocol uses either multiplicative or additive
 homomorphic encryptions.
Previously proposed protocols used additive or XOR
 homomorphic encryption schemes only.
The computation and communication costs of our protocol
 are in the same asymptotic order as those of
 the other efficient protocols.
Nevertheless, since multiplicative homomorphic encryption
 scheme is more efficient than an additive one practically,
 our construction saves computation time and communication
 bandwidth in practicality.
In comparison with the most efficient previous solution, our
 protocol saves 89% computation time and 25% communication bits.',
  keyWords='secure computation, the greater than problem',
  whenSubmitted='2005-17 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=42,
title='On the affine classification of cubic bent functions',
  authors='Sergey Agievich',
  abstract='We consider cubic boolean bent functions, each cubic monomial of which contains the same variable. We investigate canonical forms of these functions under affine transformations of variables.
In particular, we refine the affine classification of cubic bent functions of 8 variables.
',
  category='secret-key cryptography',
  keyWords='boolean functions, bent functions',
  whenSubmitted='2005-17 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=43,
title='Choosing Parameter Sets for NTRUEncrypt with NAEP and SVES-3',
  authors='Nick Howgrave-Graham; Joseph H. Silverman; William Whyte',
  abstract='We present, for the first time, an algorithm to choose parameter sets for NTRUEncrypt that give a desired level of security.

Note: This is an expanded version of a paper presented at CT-RSA 2005.',
  category='public-key cryptography',
  keyWords='encryption, ntru, lattice techniques',
  whenSubmitted='2005-17 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=44,
title='New Approaches for Deniable Authentication',
  authors='Mario Di Raimondo; Rosario Gennaro',
  abstract='Deniable Authentication protocols allow a Sender to authenticate a
message for a Receiver, in a way that the Receiver cannot convince
a third party that such authentication (or any authentication) ever
took place.

We point out a subtle definitional issue for deniability. In particular
we propose the notion of {\\em forward deniability}, which requires that
the authentications remain deniable even if the {\\em Sender} wants to later
prove that she authenticated a message. We show that generic
results where deniability is obtained by reduction to a computational
zero-knowledge protocol for an NP-complete language
do not achieve forward deniability.

We then present two new approaches to the problem of deniable authentication.
On the theoretical side, the novelty of our schemes is that they
do not require the use of CCA-secure encryption (all previous known solutions
did), thus showing a different generic approach to the problem of
deniable authentication. On the practical side, these new approaches lead
to more efficient protocols. As an added bonus, our
protocols are forward deniable.',
  category='cryptographic protocols',
  keyWords='Authentication, Deniability, Zero-Knowledge, Concurrency',
  whenSubmitted='2005-19 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=45,
title='Cryptanalysis of an anonymous wireless authentication and conference key distribution scheme',
  authors='Qiang Tang; Chris J. Mitchell',
  abstract='In this paper we analyse an anonymous wireless authentication and
conference key distribution scheme which is also designed to
provide mobile participants with user identification privacy
during the conference call. The proposed scheme consists of three
sub-protocols: the Call Set-Up Authentication Protocol, the
Hand-Off Authentication Protocol, and the Anonymous Conference
Call Protocol. We show that the proposed scheme suffers from a
number of security vulnerabilities.',
  category='cryptographic protocols',
  keyWords='wireless authentication, key agreement',
  whenSubmitted='2005-19 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=46,
title='Cryptanalysis of two identification schemes based on an ID-based cryptosystem',
  authors='Qiang Tang; Chris J. Mitchell',
  abstract='Two identification schemes based on the Maurer-Yacobi ID-based
cryptosystem are analysed and shown to suffer from serious
security problems.',
  category='cryptographic protocols',
  keyWords='identification scheme Identity-based cryptosystem',
  whenSubmitted='2005-20 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=47,
title='Adversarial Model for Radio Frequency Identification',
  authors='Gildas Avoine',
  abstract='Radio Frequency Identification (RFID) systems aim to identify objects in open environments with neither physical nor visual contact. They consist of transponders inserted into objects, of readers, and usually of a database which contains information about the objects. The key point is that authorised readers must be able to identify tags without an adversary being able to trace them. Traceability is often underestimated by advocates of the technology and sometimes exaggerated by its detractors. Whatever the true picture, this problem is a reality when it blocks the deployment of this technology and some companies, faced with being boycotted, have already abandoned its use. Using cryptographic primitives to thwart the traceability issues is an approach which has been explored for several years. However, the research carried out up to now has not provided satisfactory results as no universal formalism has been defined. 
In this paper, we propose an adversarial model suitable for RFID environments. We define the notions of existential and universal untraceability and we model the access to the communication channels from a set of oracles. We show that our formalisation fits the problem being considered and allows a formal analysis of the protocols in terms of traceability. We use our model on several well-known RFID protocols and we show that most of them have weaknesses and are vulnerable to traceability.',
  keyWords='RFID, Adversarial Model, Privacy, Untraceability, Cryptanalysis',
  whenSubmitted='2005-20 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=48,
title='David Chaum\'s Voter Verification using Encrypted Paper Receipts',
  authors='Poorvi L. Vora',
  abstract='In this document, we provide an exposition of David Chaum\'s voter
verification method that uses encrypted paper receipts. This
document provides simply an exposition of the protocol, and does
not address any of the proofs covered in Chaum\'s papers.',
  category='cryptographic protocols',
  keyWords='election schemes',
  whenSubmitted='2005-20 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=49,
title='A Note on Shor\'s Quantum  Algorithm for Prime Factorization',
  authors='Zhengjun Cao',
  abstract='It\'s well known that  Shor[1]  proposed a
polynomial time algorithm for prime factorization by using quantum
computers. For a given number \$n\$, he gave an algorithm for
finding the order \$r\$ of an element \$x\$  (mod \$n\$) instead of giving an  algorithm for factoring \$n\$ directly. The indirect
algorithm is feasible  because   factorization can be reduced to
finding the order of an element by using randomization[2]. But a
point should be stressed that the order of the number must be
even. Actually, the restriction can be removed in a particular
case. In this paper, we show that factoring RSA modulus (a product
of two primes)  only needs to find the order of \$2\$, whether it is
even or not.',
  category='foundations',
  keyWords=' Shor\'s quantum algorithm, RSA modulus.',
  whenSubmitted='2005-18 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=50,
title='Picking Virtual Pockets using Relay Attacks on Contactless Smartcard Systems',
  authors='Ziv Kfir; Avishai Wool',
  abstract='A contactless smartcard is a smartcard that can communicate with other
devices without any physical connection, using Radio-Frequency
Identifier (RFID) technology. Contactless smartcards are becoming
increasingly popular, with applications like credit-cards,
national-ID, passports, physical access. The security of such
applications is clearly critical. A key feature of RFID-based systems
is their very short range: typical systems are designed to operate at
a range of ~10cm. In this study we show that contactless
smartcard technology is vulnerable to relay attacks: An attacker can
trick the reader into communicating with a victim smartcard that is
very far away. A \"low-tech\" attacker can build a pick-pocket system
that can remotely use a victim contactless smartcard, without the
victim\'s knowledge. The attack system consists of two devices, which
we call the \"ghost\" and the \"leech\". We discuss basic designs for
the attacker\'s equipment, and explore their possible operating
ranges. We show that the ghost can be up to 50m away from the card
reader---3 orders of magnitude higher than the nominal range. We also
show that the leech can be up to 50cm away from the the victim
card. The main characteristics of the attack are: orthogonality to any
security protocol, unlimited distance between the attacker and the
victim, and low cost of the attack system.',
  category='applications',
  keyWords='RFID',
  whenSubmitted='2005-22 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=51,
  title='An Approach Towards Rebalanced RSA-CRT with Short Public Exponent',
  authors='Hung-Min Sun; Mu-En Wu',
  abstract='Based on the Chinese Remainder Theorem (CRT), Quisquater and Couvreur proposed an RSA variant, RSA-CRT, to speedup RSA decryption. According to RSA-CRT, Wiener suggested another RSA variant, Rebalanced RSA-CRT, to further speedup RSA-CRT decryption by shifting decryption cost to encryption cost. However, such an approach will make RSA encryption very time-consuming because the public exponent e in Rebalanced RSA-CRT will be of the same order of magnitude as £p(N). In this paper we study the following problem: does there exist any secure variant of Rebalanced RSA-CRT, whose public exponent e is much shorter than £p(N)? We solve this problem by designing a variant of Rebalanced RSA-CRT with d_{p} and d_{q} of 198 bits. This variant has the public exponent e=2^511+1 such that its encryption is about 3 times faster than that of the original Rebalanced RSA-CRT.',
  category='public-key cryptography',
  whenSubmitted='2005-22 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=52,
  title='Comment on cryptanalysis of Tseng et al. authenticated encryption schemes',
  authors='Yi-Hwa Chen; Jinn-Ke Jan',
  abstract='Recently, Xie and Yu proposed a forgery attack on the Tseng et al¡¦s authenticated encryption schemes and showed that their schemes are not secure in two cases: the specified verifier substitutes his secret key, or the signer generates the signature with these schemes for two or more specified verifiers. In addition, Xie and Yu made a small modification for the Tseng et al¡¦s schemes and claimed that the modified schemes can satisfy the security requirement. However, we show that the modified schemes are still insecure.',
  category='public-key cryptography',
  keyWords='Cryptography, Authenticated encryption, Message linkage, Self-certificated public key',
  whenSubmitted='2005-22 Feb', lastModified='2005-18 Mar',
  status='Withdrawn',
  comments2chair='Revise the title again.',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=53,
  title='Untraceability of Two Group Signature Schemes',
  authors='Zhengjun Cao',
  abstract='A group signature scheme allows a group member of a given group to sign messages on behalf of the group in an anonymous fashion. In case of a dispute, however, a designated group manager can reveal the signer of a valid group signature. In the paper, we show the untraceability of two group signatures in [1,5] by  new and  very simple attacks. Although those flaws, such as, forgeability, untraceability and linkability have been shown in [2,7,8,9], we should point out that our attacks are more simple.',
  category='cryptographic protocols',
  keyWords='Group signature, Untraceability.',
  whenSubmitted='2005-23 Feb',
  contact='some-email@address.edu',
  format='txt'",

"INSERT INTO {$SQLprefix}submissions SET subId=54, 
  title='Key Derivation and Randomness Extraction',
  authors='Olivier Chevassut; Pierre-Alain Fouque; Pierrick Gaudry; David Pointcheval',
  abstract='Key derivation refers to the process by which an agreed upon large
random number, often named master secret, is used to derive keys to
encrypt and authenticate data. Practitioners and standardization 
bodies have usually used the random oracle model to get key material
from a Diffie-Hellman key exchange. However, proofs in the standard model 
require randomness extractors to formally extract the entropy of the 
random master secret into a seed prior to derive other keys.

This paper first deals with the protocol \$\\Sigma_0\$, in which the key
derivation phase is (deliberately) omitted, and security inaccuracies
in the analysis and design of the Internet Key Exchange 
(IKE version 1) protocol, corrected in IKEv2. 
They do not endanger the practical use of IKEv1, since the security
could be proved, at least, in the random oracle model. 
However, in the standard model, there is not yet any formal global security 
proof, but just separated analyses which do not fit together well.
The first simplification is common in the theoretical security analysis
of several key exchange protocols, whereas the key derivation phase is a 
crucial step for theoretical reasons, but also practical purpose, and
requires careful analysis. The second problem is a gap between the
recent theoretical analysis of HMAC as a good randomness extractor
(functions keyed with public but random elements) and its practical
use in IKEv1 (the key may not be totally random, because of the lack
of clear authentication of the nonces). 
Since the latter problem comes from the probabilistic property of this 
extractor, we thereafter review some \\textit{deterministic} 
randomness extractors and suggest the \\emph{Twist-AUgmented} 
technique, a new extraction method quite well-suited for
Diffie-Hellman-like scenarios.',
  category='cryptographic protocols',
  keyWords='Key exchange, Randomness extractors, Key derivation',
  whenSubmitted='2005-25 Feb', lastModified='2005-3-19',
  contact='some-email@address.edu',
  format='pdf'",

"UPDATE {$SQLprefix}submissions SET subPwd=subId",

"INSERT INTO {$SQLprefix}committee SET name='David Beckham', revPwd='', email='rev1'",
"INSERT INTO {$SQLprefix}committee SET name='Fabio Cannavaro', revPwd='', email='rev2'",
"INSERT INTO {$SQLprefix}committee SET name='Angelos Charisteas', revPwd='', email='rev3'",
"INSERT INTO {$SQLprefix}committee SET name='Joy Fawcett', revPwd='', email='rev4'",
"INSERT INTO {$SQLprefix}committee SET name='Danielle Fotopoulos', revPwd='', email='rev5'",
"INSERT INTO {$SQLprefix}committee SET name='Julie Foudy', revPwd='', email='rev6'",
"INSERT INTO {$SQLprefix}committee SET name='Stylianos Giannakopoulos', revPwd='', email='rev7'",
"INSERT INTO {$SQLprefix}committee SET name='Mia Hamm', revPwd='', email='rev8'",
"INSERT INTO {$SQLprefix}committee SET name='Devvyn Hawkins', revPwd='', email='rev9'",
"INSERT INTO {$SQLprefix}committee SET name='Angela Hucles', revPwd='', email='rev10'",
"INSERT INTO {$SQLprefix}committee SET name='Jena Kluegel', revPwd='', email='rev11'",
"INSERT INTO {$SQLprefix}committee SET name='Amy LePeilbet', revPwd='', email='rev12'",
"INSERT INTO {$SQLprefix}committee SET name='Paolo Maldini', revPwd='', email='rev13'",
"INSERT INTO {$SQLprefix}committee SET name='Antonios Nikopolidis', revPwd='', email='rev14'",
"INSERT INTO {$SQLprefix}committee SET name='Michael Owen', revPwd='', email='rev15'",
"INSERT INTO {$SQLprefix}committee SET name='Francesco Totti', revPwd='', email='rev16'",
"INSERT INTO {$SQLprefix}committee SET name='Zinedine Zidane', revPwd='', email='rev17'",
"INSERT INTO {$SQLprefix}assignments (subId,revId,pref,compatible) VALUES (1,2,2,0),(1,3,3,0),(1,5,2,0),(1,8,2,0),(1,9,3,0),(1,10,1,0),(1,11,1,0),(1,12,3,0),(1,13,2,0),(1,14,2,0),(1,15,4,0),(1,16,5,0),(1,17,1,0),(1,18,2,0),(2,10,3,0),(2,12,5,0),(2,13,1,0),(2,14,4,0),(2,2,2,0),(2,4,4,0),(2,6,4,0),(2,7,3,0),(2,9,1,0),(3,10,2,0),(3,13,3,0),(3,15,1,0),(3,16,4,0),(3,17,1,0),(3,18,1,0),(3,3,2,0),(3,5,4,0),(3,6,4,0),(3,7,4,0),(3,9,1,0),(4,10,4,0),(4,11,0,0),(4,12,3,0),(4,13,1,0),(4,14,2,0),(4,15,1,0),(4,16,3,-1),(4,17,3,0),(4,2,2,0),(4,18,3,0),(4,3,2,0),(4,4,3,1),(4,7,3,0),(4,8,4,0),(4,9,2,0),(5,10,3,0),(5,11,3,0),(5,12,2,0),(5,13,3,0),(5,14,1,0),(5,15,5,0),(5,16,1,0),(5,17,4,0),(5,2,3,0),(5,18,5,0),(5,3,2,0),(5,4,4,0),(5,5,2,0),(5,7,1,0),(5,8,4,0),(5,9,2,0),(6,3,4,0),(6,4,1,0),(6,5,1,0),(6,7,5,0),(6,8,5,0),(6,9,2,0),(6,10,1,0),(6,11,2,0),(6,12,2,0),(6,13,2,0),(6,14,5,0),(6,15,5,0),(6,16,2,0),(6,17,5,0),(6,18,1,0),(6,2,2,0),(7,10,3,0),(7,11,3,0),(7,13,3,0),(7,14,4,0),(7,16,2,0),(7,17,0,0),(7,18,3,0),(7,4,2,0),(7,5,5,0),(7,6,5,0),(7,9,3,0),(8,10,3,0),(8,14,2,0),(8,15,2,0),(8,16,2,0),(8,17,1,0),(8,2,2,0),(8,18,2,0),(8,3,3,0),(8,4,0,0),(8,6,3,0),(8,7,3,0),(8,8,3,0),(8,9,3,0),(9,11,3,0),(9,12,5,0),(9,13,4,0),(9,15,1,0),(9,16,2,0),(9,17,3,0),(9,3,3,0),(9,4,4,0),(9,5,4,0),(9,8,2,0),(9,9,2,0),(10,3,5,0),(10,4,4,0),(10,5,3,0),(10,6,0,0),(10,7,4,0),(10,8,2,0),(10,9,2,0),(10,10,1,0),(10,11,5,0),(10,13,3,0),(10,14,1,0),(10,15,1,0),(10,17,4,0),(10,18,5,0)",
"INSERT INTO {$SQLprefix}assignments (subId,revId,pref,compatible) VALUES (11,10,2,0),(11,11,3,0),(11,12,4,0),(11,13,2,0),(11,15,2,0),(11,16,1,0),(11,18,3,0),(11,3,5,0),(11,6,1,0),(11,8,3,0),(11,9,2,0),(12,11,2,0),(12,12,1,0),(12,13,2,0),(12,14,0,0),(12,15,2,0),(12,16,1,0),(12,17,2,0),(12,2,1,0),(12,18,1,0),(12,4,3,0),(12,5,2,0),(12,7,2,0),(12,8,3,0),(12,9,2,0),(13,11,2,0),(13,15,4,0),(13,16,1,0),(13,17,3,0),(13,2,3,0),(13,18,3,0),(13,6,1,0),(13,7,2,0),(14,10,2,0),(14,11,2,0),(14,12,2,0),(14,14,2,0),(14,15,1,0),(14,16,1,0),(14,17,1,0),(14,2,4,0),(14,18,2,0),(14,3,4,0),(14,4,3,0),(14,5,4,0),(15,11,4,0),(15,12,2,0),(15,13,2,0),(15,14,2,0),(15,15,1,0),(15,16,1,0),(15,18,2,0),(15,3,4,0),(15,4,2,0),(15,5,3,0),(15,6,4,0),(15,8,2,0),(16,10,2,0),(16,12,2,0),(16,13,1,0),(16,14,2,0),(16,15,4,0),(16,16,1,0),(16,17,1,0),(16,18,2,0),(16,3,3,0),(16,4,2,0),(16,5,2,0),(16,6,2,0),(16,7,3,0),(16,8,3,0),(16,9,3,0),(17,10,3,0),(17,11,3,0),(17,13,3,0),(17,14,2,0),(17,15,3,0),(17,17,3,0),(17,3,2,0),(17,4,3,0),(17,5,2,0),(17,6,5,0),(17,7,4,0),(17,8,4,0),(17,9,2,0),(18,10,3,0),(18,11,1,0),(18,12,4,0),(18,13,2,0),(18,14,1,0),(18,15,0,0),(18,16,4,0),(18,17,2,0),(18,3,3,0),(18,4,4,0),(18,5,3,0),(18,8,2,0),(18,9,2,0),(19,10,2,0),(19,11,1,0),(19,12,3,0),(19,14,1,0),(19,15,4,0),(19,16,2,0),(19,18,4,0),(19,3,1,0),(19,5,4,0),(19,7,4,0),(19,9,1,0)",
"INSERT INTO {$SQLprefix}assignments (subId,revId,pref,compatible) VALUES (20,10,3,0),(20,11,2,0),(20,13,2,0),(20,14,4,0),(20,16,4,0),(20,17,2,0),(20,2,3,0),(20,3,1,0),(20,6,4,0),(20,8,3,0),(20,9,1,0),(21,10,4,0),(21,12,4,0),(21,13,2,0),(21,14,5,0),(21,15,4,0),(21,17,2,0),(21,2,3,0),(21,18,4,0),(21,3,2,0),(21,4,2,0),(21,6,3,0),(21,8,4,0),(22,10,3,0),(22,11,4,0),(22,14,1,0),(22,15,3,0),(22,16,4,0),(22,17,4,0),(22,18,3,0),(22,5,4,0),(22,7,1,0),(22,9,1,0),(23,11,3,0),(23,12,2,0),(23,13,2,0),(23,14,5,0),(23,2,4,0),(23,3,2,0),(23,6,3,0),(23,7,3,0),(23,8,5,0),(24,10,3,0),(24,11,4,0),(24,13,5,0),(24,17,5,0),(24,2,2,0),(24,3,4,0),(24,4,1,0),(24,5,1,0),(24,6,4,0),(24,7,2,0),(24,9,2,0),(25,10,1,0),(25,11,3,0),(25,12,1,0),(25,13,3,0),(25,14,2,0),(25,15,2,0),(25,16,1,0),(25,17,3,0),(25,18,5,0),(25,3,3,0),(25,5,2,0),(25,7,3,0),(25,9,2,0),(26,10,1,0),(26,11,2,0),(26,12,0,0),(26,13,3,0),(26,14,2,0),(26,16,1,0),(26,17,2,0),(26,3,3,0),(26,4,5,0),(26,5,2,0),(26,6,4,0),(26,7,4,0),(26,8,1,0),(26,9,3,0),(27,10,5,0),(27,11,2,0),(27,12,2,0),(27,13,1,0),(27,14,4,0),(27,15,0,0),(27,16,2,0),(27,17,1,0),(27,2,1,0),(27,18,2,0),(27,3,2,0),(27,5,5,0),(27,7,1,0),(27,8,1,0),(27,9,4,0),(28,10,3,0),(28,13,3,0),(28,15,3,0),(28,16,2,0),(28,3,2,0),(28,4,4,0),(28,5,3,0),(28,6,2,0),(28,9,3,0),(29,11,3,0),(29,12,3,0),(29,13,3,0),(29,15,1,0),(29,16,2,0),(29,17,2,0),(29,2,1,0),(29,4,3,0),(29,6,2,0),(29,8,2,0),(29,9,3,0)",
"INSERT INTO {$SQLprefix}assignments (subId,revId,pref,compatible) VALUES (30,14,3,0),(30,15,1,0),(30,16,2,0),(30,17,2,0),(30,2,4,0),(30,18,3,0),(30,3,4,0),(30,8,2,0),(31,13,2,0),(31,14,3,0),(31,17,3,0),(31,2,1,0),(31,18,2,0),(31,3,2,0),(31,4,5,0),(31,7,3,0),(31,9,3,0),(32,10,3,0),(32,12,4,0),(32,13,2,0),(32,14,3,0),(32,15,2,0),(32,16,2,0),(32,17,3,0),(32,2,3,0),(32,18,1,0),(32,3,3,0),(32,4,3,0),(32,5,2,0),(32,6,1,0),(32,8,1,0),(33,10,3,0),(33,13,2,0),(33,14,3,0),(33,15,3,0),(33,16,1,0),(33,17,2,0),(33,2,3,0),(33,18,2,0),(33,3,5,0),(33,4,0,0),(33,6,1,0),(33,7,3,0),(33,9,3,0),(34,10,3,0),(34,11,2,0),(34,12,4,0),(34,13,2,0),(34,2,4,0),(34,5,4,0),(34,8,3,0),(34,9,3,0),(35,10,3,0),(35,11,2,0),(35,13,2,0),(35,14,4,0),(35,16,1,0),(35,18,3,0),(35,3,4,0),(35,5,3,0),(35,8,4,0),(35,9,3,0),(36,10,2,0),(36,11,2,0),(36,13,2,0),(36,14,4,0),(36,15,4,0),(36,2,1,0),(36,3,3,0),(36,4,3,0),(36,5,4,0),(36,6,0,0),(36,7,4,0),(36,8,5,0),(37,10,5,0),(37,11,4,0),(37,16,3,0),(37,17,3,0),(37,2,5,0),(37,18,4,0),(37,4,4,0),(37,5,3,0),(37,6,4,0),(37,7,4,0),(37,9,4,0),(38,10,2,0),(38,12,4,0),(38,14,3,0),(38,15,1,0),(38,16,3,0),(38,17,2,0),(38,3,2,0),(38,5,3,0),(38,7,4,0),(38,8,3,0),(38,9,3,0),(39,10,3,0),(39,11,2,0),(39,13,4,0),(39,14,2,0),(39,15,1,0),(39,16,2,0),(39,17,3,0),(39,18,2,0),(39,3,3,0),(39,4,5,0),(39,5,2,0),(39,6,3,0),(39,7,4,0)",
"INSERT INTO {$SQLprefix}assignments (subId,revId,pref,compatible) VALUES (40,10,3,0),(40,12,3,0),(40,13,4,0),(40,14,3,0),(40,17,3,0),(40,2,4,0),(40,18,3,0),(40,3,3,0),(40,5,2,0),(40,8,3,0),(41,10,3,0),(41,11,4,0),(41,12,2,0),(41,13,0,0),(41,15,3,0),(41,16,3,0),(41,17,3,0),(41,2,4,0),(41,18,1,0),(41,3,4,0),(41,6,2,0),(42,13,5,0),(42,14,1,0),(42,15,4,0),(42,16,2,0),(42,17,4,0),(42,2,1,0),(42,18,3,0),(42,4,4,0),(42,5,1,0),(42,6,3,0),(42,7,3,0),(42,9,3,0),(43,11,2,0),(43,12,2,0),(43,13,3,0),(43,15,1,0),(43,16,4,0),(43,17,3,0),(43,18,3,0),(43,3,2,0),(43,4,1,0),(43,5,3,0),(43,6,3,0),(43,7,2,0),(43,8,4,0),(43,9,2,0),(44,10,2,0),(44,11,2,0),(44,14,4,0),(44,16,3,0),(44,18,4,0),(44,4,3,0),(44,7,2,0),(44,8,3,0),(44,9,2,0),(45,10,2,0),(45,11,3,0),(45,12,4,0),(45,14,5,0),(45,15,3,0),(45,3,4,0),(45,5,2,0),(45,6,4,0),(45,7,5,0),(45,9,2,0),(46,10,2,0),(46,11,4,0),(46,12,3,0),(46,13,5,0),(46,14,3,0),(46,15,1,0),(46,16,2,0),(46,17,5,0),(46,2,4,0),(46,5,2,0),(46,9,2,0),(47,10,2,0),(47,11,3,0),(47,12,5,0),(47,13,1,0),(47,16,1,0),(47,17,3,0),(47,2,3,0),(47,3,2,0),(47,6,4,0),(47,7,3,0),(47,8,3,0),(47,9,2,0),(48,10,3,0),(48,11,4,0),(48,14,2,0),(48,15,5,0),(48,17,1,0),(48,2,3,0),(48,18,1,0),(48,3,4,0),(48,4,4,0),(48,5,3,0),(48,6,4,0),(48,7,1,0),(48,8,2,0),(48,9,2,0),(49,10,4,0),(49,11,5,0),(49,12,2,0),(49,13,2,0),(49,14,2,0),(49,16,1,0),(49,17,1,0),(49,2,5,0),(49,18,3,0),(49,3,2,0),(49,5,5,0),(49,8,2,0),(49,9,2,0)",
"INSERT INTO {$SQLprefix}assignments (subId,revId,pref,compatible) VALUES (50,10,3,0),(50,11,2,0),(50,12,1,0),(50,13,4,0),(50,14,1,0),(50,15,1,0),(50,16,0,0),(50,17,5,0),(50,18,2,0),(50,3,2,0),(50,4,5,0),(50,5,4,0),(50,6,4,0),(50,7,1,0),(50,8,1,0),(50,9,1,0),(51,11,1,0),(51,12,4,1),(51,15,2,0),(51,17,2,0),(51,3,4,0),(51,4,2,0),(51,5,4,0),(51,7,4,0),(51,8,2,0),(52,10,4,0),(52,12,2,0),(52,14,2,0),(52,15,4,0),(52,17,3,0),(52,2,4,0),(52,18,1,0),(52,3,4,0),(52,4,5,0),(52,5,2,0),(52,6,2,0),(52,8,3,0),(52,9,3,0),(53,10,2,0),(53,11,3,0),(53,13,2,0),(53,15,2,0),(53,16,4,0),(53,17,5,0),(53,2,1,0),(53,18,4,0),(53,4,2,0),(53,5,2,0),(53,6,5,0),(53,7,2,0),(53,8,2,0),(53,9,4,0),(54,2,4,0),(54,3,4,1),(54,4,1,0),(54,5,2,-1),(54,6,2,0),(54,7,2,0),(54,8,1,0),(54,9,5,0),(54,10,0,0),(54,11,3,1),(54,13,4,0),(54,14,4,0),(54,18,4,0)");

for ($i = 0; $i < count($queries); $i++) pdo_query($queries[$i]);

// if the submission directory is not BASE/subs/ then copy all
// the files from BASE/subs/ to the submission directory

$qry = "SELECT subId, format FROM {$SQLprefix}submissions";
$res = pdo_query($qry);
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $subId = $row[0];
  $fmt = $row[1];
  if (!file_exists(SUBMIT_DIR."/$subId.$fmt"))
    copy("../subs/$subId.$fmt", SUBMIT_DIR."/$subId.$fmt");
}
header("Location: receiptCustomize.php?testOnly=yes{$urlParams}");
?>
