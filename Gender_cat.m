% extract data

len = length( Name );
col_g = zeros( len, 1 );

for i = 1:len
    if strcmp(Gender{i}, 'Female') ~= 0
        col_g(i) = 1;
        
    elseif strcmp(Gender{i}, 'Male') ~= 0
        col_g(i) = 2;
    else
        col_g(i) = 0;
        
    end
end

% for i = 1 : len
%     disp(col_g(i));
% end

disp(col_g);

fileID = fopen('gender_cat.txt','w');
for i = 1 : len
   fprintf(fileID,'%6s %2d\n',Name{i}, col_g(i));
%     fprintf(fileID,'%2d\n',col_g(i));

end
